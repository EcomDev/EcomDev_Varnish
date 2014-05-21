import std;

include "includes/balancer.vcl";
include "includes/devicedetect.vcl";
include "includes/acl.vcl";
include "includes/admin.vcl";
include "includes/functions.vcl";

# Handle the HTTP request received by the client
sub vcl_recv {
    # shortcut for DFind requests
    if (req.url ~ "^/w00tw00t") {
        error 404 "Not Found";
    }


    call detect_admin;
    
    set client.identity = req.http.User-Agent + " " + client.ip;
    
    call normalize_url;
    call normalize_cookie;
    call normalize_gzip_ua;
    call normalize_customer_segment;
    
    call devicedetect;

    # Deny access to admin, if not in list of allowed ip
    if (req.http.is-admin && !client.ip ~ allow_admin) {
        error 403 "Forbidden";
    } elsif (req.http.is-admin) {
        if (req.http.X-Real-Ip && !req.http.is-admin-user) {
            error 403 "Forbidden";
        }
        set req.backend = admin;
        return (pass);
    } else {
        set req.backend = balancer;
    }
    
    if (req.http.X-Real-Ip && client.ip ~ is_local) {
        set req.http.X-Secure = "1";
    } else {
        set req.http.X-Secure = "0";
    }
    
    if (req.restarts == 0) {
        if (req.http.X-Real-Ip && client.ip ~ is_local) {
            set req.http.X-Forwarded-For = req.http.X-Real-Ip;
        } else {
            set req.http.X-Forwarded-For = client.ip;
        }
    }

    # Normalize the header, remove the port (in case you're testing this on various TCP ports)
    set req.http.Host = regsub(req.http.Host, ":[0-9]+", "");

    # Accept pages purge from authorized browsers' CTRl+F5
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ allow_refresh) {
        if (!req.http.X-Real-Ip || req.http.is-refresh-user) {
            # Forces current page to have a cache miss
            set req.hash_always_miss = true;
        }
    }

    # Only deal with "normal" types
    if (req.request != "GET" &&
            req.request != "HEAD" &&
            req.request != "PUT" &&
            req.request != "POST" &&
            req.request != "TRACE" &&
            req.request != "OPTIONS" &&
            req.request != "PATCH" &&
            req.request != "DELETE") {
        /* Non-RFC2616 or CONNECT which is weird. */
        return (pipe);
    }

    # Large static files should be piped, so they are delivered directly to the end-user without
    # waiting for Varnish to fully read the file first.
    # TODO: once the Varnish Streaming branch merges with the master branch, use streaming here to avoid locking.
    if (req.url ~ "^[^?]*\.(mp[34]|rar|tar|tgz|gz|wav|zip)(\?.*)?$") {
        return (pipe);
    }

    # Remove all cookies for static files
    # Static files are cached by default
    if (req.url ~ "^[^?]*\.(bmp|bz2|css|doc|eot|flv|gif|gz|ico|jpeg|jpg|js|less|pdf|png|rtf|swf|txt|woff|xml|css\.map)(\?.*)?$") {
        unset req.http.Cookie;
        return (lookup);
    }
    
    if (req.http.Authorization) {
        # Not cacheable by default
        return (pass);
    }

    if (req.request == "POST") {
        return (pass);
    }

    return (lookup);
}

# The data on which the hashing will take place
sub vcl_hash {
    hash_data(req.url);

    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }

    # hash device for request
    if (req.http.X-UA-Device) {
        hash_data(req.http.X-UA-Device);
    }

    if (req.http.X-Secure) {
        hash_data(req.http.X-Secure);
    }
    
    if (req.http.X-Cache-Segment) {
        hash_data(req.http.X-Cache-Segment);
    }

    return (hash);
}


# Handle the HTTP request coming from our backend
sub vcl_fetch {

    # Parse ESI request and remove Surrogate-Control header
    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
	set beresp.do_esi = true;
    }

    # Enable gzip compression, if header for it is specified
    if (beresp.http.X-Cache-Gzip) {
        remove beresp.http.X-Cache-Gzip;
        set beresp.do_gzip = true;
    }

    # Sometimes, a 301 or 302 redirect formed via Apache's mod_rewrite can mess with the HTTP port that is being passed along.
    # This often happens with simple rewrite rules in a scenario where Varnish runs on :80 and Apache on :8080 on the same box.
    # A redirect can then often redirect the end-user to a URL on :8080, where it should be :80.
    # This may need finetuning on your setup.
    #
    # To prevent accidental replace, we only filter the 301/302 redirects for now.
    if (beresp.status == 301 || beresp.status == 302) {
        set beresp.http.Location = regsub(beresp.http.Location, ":[0-9]+", "");
	    return (hit_for_pass);
    }

    if (beresp.status == 404) {
        unset beresp.http.Set-Cookie;
    	return (hit_for_pass);
    }

    # comment this out if you don't want the client to know your classification
    set beresp.http.X-UA-Device = req.http.X-UA-Device;
    set beresp.http.X-Cache-Deviation = req.http.X-Cache-Deviation;

    # Remove all cookies for static files and cache them for 1hour
    if (req.url ~ "^[^?]*\.(bmp|bz2|css|doc|eot|flv|gif|gz|ico|jpeg|jpg|js|less|pdf|png|rtf|swf|txt|woff|xml|css\.map)(\?.*)?$") {
        unset req.http.Cookie;
        set beresp.ttl = 1h;
        return (deliver);
    }
  
    if (beresp.http.X-Cache-Ttl) {
        set beresp.ttl = std.duration(beresp.http.X-Cache-Ttl, 0s);
        unset beresp.http.Set-Cookie;
    } else {
        return (hit_for_pass);
    }

    return (deliver);
}

# The routine when we deliver the HTTP request to the user
# Last chance to modify headers that are sent to the client
sub vcl_deliver {
    if (obj.hits > 0) {
        set resp.http.X-Cache = "cached";
    } else {
        set resp.http.X-Cache = "uncached";
    }

    # Remove some headers: PHP version
    unset resp.http.X-Powered-By;

    # Remove some headers: Apache version & OS
    unset resp.http.Server;
    unset resp.http.X-Varnish;
    unset resp.http.Via;
    unset resp.http.Link;

    return (deliver);
}


