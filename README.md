# README #

### LTI Module for ExpressionEngine(EE)  ###
Tested with up to EE

## Version 3.3.38 ##
More intelligent use of tags, easier to develop custom templates.
Bootstrap
Bootbox modals
Bug fixes

** Version 3.2.2 **
 Added BB_EMBED get parameter to allow embedding of tools in Blackboard
using a token generated with the remember_lti_user plugin.
 Incorporated UoN Rails app for user creation of LTI links.

** Version 3.2.0 **
 removed total reliance on EE sessions
 fixed javascript bug in rubric

** Version 3.0.0 **
 Ajax endpoint for uploading rubrics from external tools
  New ACT category in extension hooks to allow direct action requests
 Link generation key added to blti_keys table for external link and template generation

** Version 2.24 **
 [Clickjack](https://www.owasp.org/index.php/Clickjacking) vulnerability has been addressed in this version

# Description #
Provides template tags, grade write and grade read functions for integration into other systems using the Learning Tools Interoperability™ (LTI) protocol. LTI is used for cross domain access to external learning tools in the leading learning management systems (LMSs) and virtual learning environments (VLEs) used by educational institutions, such as Moodle, Blackboard Learn and Instructure Canvas. Once 'launched' from the LMS the user is automatically logged in using oAuth  and added to the Members table based on the LTI context sent from the LMS. Control panel access allows addition of an unlimited amount of LTI providers, each on different template url segments on the site. This module and extension use IMS Global's LTI Specification version .

Developed at the Centre for Teaching and Learning at the University of Newcastle (UoN), this tool is in constant development and used in on-line Blackboard courses.

It has had rudimentary testing in Moodle, further development is desired in this direction.

### Getting started ###

 Get a copy of [EE Core at Ellislabs](https://store.ellislab.com/#ee-core) (note this is limited to  back-end administrative and content development users, you will need to pay for the full version if you need more administrators).
 [Download the zip file of the latest release at devot:ee](https://devot-ee.com/add-ons/learning-tools-integration)
 Follow the instructions at the above link and the [installing add-ons section](https://docs.expressionengine.com/latest/cp/addons/index.html) of the EE documentation.

The extension_hooks directory is in a [separate repository](https://bitbucket.org/sijpkes/ee-lti-extension-hooks/overview) as this is where most of the LMS specific code is housed.  If you would like to contribute and build specific extensions to suit other LMSs, you are more than welcome!

#Extension Hooks for EE LTI Module

##Tags available for integration with Blackboard Learn##

##User and Group Import##
EE Tags provided
```
#!html
{student_table}
{upload_student_list}
```
Allows manual and automated import of Users and/or Groups using CSV or with secure authentication to Blackboard Learns Gradebook JSON export REST URI.

##Blackboard Rubrics##
EE Tags provided
```
#!html

{upload_blackboard_rubric}
{render_blackboard_rubric}
```
Provides tags that allow import and display of rubric ZIP archives exported from Blackboard learn, provides tags for viewing the Rubrics.  These can be used with the Peer Assessment plugin below.

##Secure Resource Delivery##
EE Tags provided
```
#!html
{download_resource}
{random_form_error}
{random_form}
{random_remainder_form}
{resource_settings_form}
{resource_table}
{upload_student_resources_form}
```
This extension provides tags to allow the setup of randomly assigned question and answer resources to students.  A link is placed in Blackboard for the question and the solution.  The solution can then be released via an adaptive release rule at the Instructor's discretion.

##Settings##
EE Tags Provided
```
#!html
{general_settings_form}
```
This tag provides a settings form for instructors to turn plugins and features on and off, it can be restricted to certain users.

## Gradebook User and Group import ##
This extension runs automatically once added and will import all users from the LMS after it is provided with the Instructor's password. The password is encryped using the [Defuse encryption library](https://github.com/defuse/php-encryption)
### Config File ###
The gradebook import and group functions require some URIs to Blackboards services.  These are set in the config.php file located here:
```
#!shell

ee_install_dir/system/user/addons/learning_tools_integration/config
```

#EE Plugins#

###[Peer Review Module](https://bitbucket.org/sijpkes/lti-peer-assessment)###
This EE plugin is in constant development as I get more interest from academics here at UoN.  Grab this from BitBucket via the above link.

This plugin requires all the above extension hooks to run
This plugin has ONLY been tested in Blackboard Learn.



### Contribution guidelines ###

Contributors are need, so if you're interested in contributing please contact [Paul Sijpkes](mailto:paul.sijpkes@newcastle.edu.au) at the University of Newcastle, Australia (PH +).
