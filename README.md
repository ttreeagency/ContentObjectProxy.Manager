# Content Object Proxy Manager

This package contains a backend module for Neos CMS to manager content object proxy (doctrine entities proxied in the
content repository).

## Installation 

To use the package, you have to install this package
	
	composer require "ttree/contentobjectproxy-manager"
	
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
