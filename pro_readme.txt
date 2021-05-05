These additions setup the pro architecture of a plugin.

# Rules of integration

- Copy files into the plugin
- replace %PLUGINPATH%, %PLUGINTYPE% and %PLUGINNAME%
- include lang/xx/pro_addition_strings.php into lang files (foreach xx)
- add function 'xx_supports_feature' to lib.php. Create lib.php if needed.

- recomile (grunt by syncworking) (build the amd/build)
- makepro (build the obfuscated /pro set)

# Developping protected pro features

- Add pro feature/subfeature key 
- implement specific code in pro_src libraries
- implement screens and services in pro_src
- add calls to pro-zone controlled by xxx_supports_feature('feature/subfeature') test.
- compile pro zone (makepro) 