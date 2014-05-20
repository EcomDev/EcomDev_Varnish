import std;

include "includes/balancer.vcl";
include "includes/devicedetect.vcl";
include "includes/acl.vcl";
include "includes/admin.vcl";

# Normalizes cookie values, to remove Adwords and GoogleAnalitics cookies
sub cookies_normalize {
    # Some generic cookie manipulation, useful for all templates that follow
     # Remove the "has_js" cookie
     set req.http.Cookie = regsuball(req.http.Cookie, "has_js=[^;]+(; )?", "");
 
     # Remove any Google Analytics based cookies
     set req.http.Cookie = regsuball(req.http.Cookie, "__utm.=[^;]+(; )?", "");
     set req.http.Cookie = regsuball(req.http.Cookie, "_ga=[^;]+(; )?", "");
     set req.http.Cookie = regsuball(req.http.Cookie, "utmctr=[^;]+(; )?", "");
     set req.http.Cookie = regsuball(req.http.Cookie, "utmcmd.=[^;]+(; )?", "");
     set req.http.Cookie = regsuball(req.http.Cookie, "utmccn.=[^;]+(; )?", "");
 
     # Remove the Quant Capital cookies (added by some plugin, all __qca)
     set req.http.Cookie = regsuball(req.http.Cookie, "__qc.=[^;]+(; )?", "");
 
     # Remove the AddThis cookies
     set req.http.Cookie = regsuball(req.http.Cookie, "__atuvc=[^;]+(; )?", "");
 
     # Remove a ";" prefix in the cookie if present
     set req.http.Cookie = regsuball(req.http.Cookie, "^;\s*", "");
 
     # Are there cookies left with only spaces or that are empty?
     if (req.http.cookie ~ "^\s*$") {
         unset req.http.cookie;
     }
}

# Normalizes url to exclude GoogleAnalitics, Adwords, FB and other marketing systems
# tracking parameters, to not make a page unique for a cache engine
sub url_normalize {
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

    # Some generic URL manipulation, useful for all templates that follow
    # First remove the Google Analytics added parameters, useless for our backend
    if (req.url ~ "(\?|&)(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=") {
        set req.url = regsuball(req.url, "&(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "");
        set req.url = regsuball(req.url, "\?(utm_source|utm_medium|utm_campaign|gclid|cx|ie|cof|siteurl)=([A-z0-9_\-\.%25]+)", "?");
        set req.url = regsub(req.url, "\?&", "?");
        set req.url = regsub(req.url, "\?$", "");
    }

    # Strip hash, server doesn't need it.
    if (req.url ~ "\#") {
        set req.url = regsub(req.url, "\#.*$", "");
    }

    # Strip a trailing ? if it exists
    if (req.url ~ "\?$") {
        set req.url = regsub(req.url, "\?$", "");
    }
}

# Handle the HTTP request received by the client
sub vcl_recv {
    # shortcut for DFind requests
    if (req.url ~ "^/w00tw00t") {
        error 404 "Not Found";
    }

    call detect_admin;
    call cookies_normalize;
    
    set client.identity = req.http.cookie;
    
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

    call devicedetect;

    # Accept pages purge from authorized browsers' CTRl+F5
    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ allow_refresh) {
        if (!req.http.X-Real-Ip || req.http.is-refresh-user) {
            # Forces current page to have a cache miss
            set req.hash_always_miss = true;
        }
    }

    call url_normalize;    

    # Normalize Accept-Encoding header
    # straight from the manual: https://www.varnish-cache.org/docs/3.0/tutorial/vary.html
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|png|gif|gz|tgz|bz2|tbz|mp3|ogg)$") {
            # No point in compressing these
            remove req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            # unkown algorithm
            remove req.http.Accept-Encoding;
        }
    }

    # Large static files should be piped, so they are delivered directly to the end-user without
    # waiting for Varnish to fully read the file first.
    # TODO: once the Varnish Streaming branch merges with the master branch, use streaming here to avoid locking.
    if (req.url ~ "^[^?]*\.(mp[34]|rar|tar|tgz|gz|wav|zip)(\?.*)?$") {
        return (pipe);
    }

    # Remove all cookies for static files and disable caching of them, by passing them further
    if (req.url ~ "^[^?]*\.(bmp|bz2|css|doc|eot|flv|gif|gz|ico|jpeg|jpg|js|less|pdf|png|rtf|swf|txt|woff|xml|css\.map)(\?.*)?$") {
        return (pass);
    }

    # Send Surrogate-Capability headers to announce ESI support to backend
    set req.http.Surrogate-Capability = "key=ESI/1.0";
    set req.http.User-Agent = req.http.User-Agent + " " + req.http.X-UA-Device;

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
    }

    if (beresp.status == 404) {
        unset beresp.http.Set-Cookie;
    }

    # comment this out if you don't want the client to know your classification
    set beresp.http.X-UA-Device = req.http.X-UA-Device;

    if (beresp.http.X-Cache-Ttl) {
        set beresp.ttl = std.duration(beresp.http.X-Cache-Ttl, 0s);
        unset beresp.http.Set-Cookie;	
    } else   {
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
