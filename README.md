# Content Object Proxy Manager

**Warning**: This package is NOT generic, it's developped for one of our big project, using this package outside of the 
project will not work. But many feature can be use in a generic way and if we need this package for an other project, we 
will refactor it. We decide to opensource the package, because you can learn some stuff by reading the code. Enjoy.

This package contains a backend module for Neos CMS to manager content object proxy (doctrine entities proxied in the
content repository).

## Installation 

To use the package, you have to install this package
	
	composer require "ttree/contentobjectproxy-manager"
	
## Screenshot

![Basic Backend Module](http://g.recordit.co/N9v1hp4kpx.gif)

## Actions

### Refresh all entities

Synchronize to content of the doctrine entities to the content repository.

### Rename one entity

Change the name (label) of one entity, can also update the URI path segment if this information is 
managed by the doctrine entity.

### Remove one entity

Remove the doctrine entities and all proxy nodes. Can check if the proxy node contains child nodes and replace the 
removed entity by an other one.

### Custom actions

You can register custom actions, see bellow

TODO
