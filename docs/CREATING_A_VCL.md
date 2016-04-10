# Creating a VCL
As you might have seen already, this extension contains a shell script under the shell folder.
This script allows you to generate a vcl, ban cache for a single object and ban cache for multiple objects.
Using the vcl:generate command, you can generate the vcl you need for your setup.
The command expects the input of a config.json.

## Defining a template file
A template file can be defined with the -f command.

In this extension, the default template is the Varnish 4 template located here:
```
app/design/shell/base/default/template/ecomdev/varnish/vcl/v4/config.phtml
```

When you use Varnish 3, you can use the template located here:
```
app/design/shell/base/default/template/ecomdev/varnish/vcl/v3/config.phtml
```

## Defining a config file
A config file can be defined with the -c command.

A complete config.json looks like this:
```
ADD CONFIG JSON HERE
```