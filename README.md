# README #

### LTI Module for ExpressionEngine (EE)  ###
Tested with up to ExpressionEngine 3.5.3

## New in Version 3.4.1 ##
* Moved pagination icons location references from template to config file
* Added AJAX LMS grade read and write tags

[Change Log](CHANGE_LOG.md)

# Description #
Provides template tags, grade write and grade read functions for integration into other systems using the Learning Tools Interoperability™ (LTI) protocol. LTI is used for cross domain access to external learning tools in the leading learning management systems (LMSs) and virtual learning environments (VLEs) used by educational institutions, such as Moodle, Blackboard Learn and Instructure Canvas. Once 'launched' from the LMS the user is automatically logged in using oAuth  and added to the Members table based on the LTI context sent from the LMS. Control panel access allows addition of an unlimited amount of LTI providers, each on different template url segments on the site. This module and extension use IMS Global's LTI Specification version .

Developed at the Centre for Teaching and Learning at the University of Newcastle (UoN), this tool is in constant development and used in on-line Blackboard courses.

It has had rudimentary testing in Moodle, further development is desired in this direction.

### Getting started ###

 Get a copy of [EE Core at Ellislabs](https://store.ellislab.com/#ee-core) (note this is limited to 3 back-end administrative and content development users, you will need to pay for the full version if you need more administrators).
 Download the zip file of the latest release at [Github](https://github.com/BOLDLab/learning_tools_integration)
 Or clone it with git:
 ```
 git clone git@github.com:BOLDLab/learning_tools_integration.git
 ```
 Follow the instructions at the above link and the [installing add-ons section](https://docs.expressionengine.com/latest/cp/addons/index.html) of the EE documentation.

# Extension Hooks for EE LTI Module #
Template tags available for integration with Blackboard Learn
## User and Group Import ##

```
{student_table}
{upload_student_list}
```
Allows manual and automated import of Users and/or Groups using CSV or with secure authentication to Blackboard Learns Gradebook JSON export REST URI.

## Blackboard Rubrics ##


```
{upload_blackboard_rubric}
{render_blackboard_rubric}
```
Provides tags that allow import and display of rubric ZIP archives exported from Blackboard learn, provides tags for viewing the Rubrics.  These can be used with the Peer Assessment plugin below.

## Secure Resource Delivery ##


```
{download_resource}
{random_form_error}
{random_form}
{random_remainder_form}
{resource_settings_form}
{resource_table}
{upload_student_resources_form}
```
This extension provides tags to allow the setup of randomly assigned question and answer resources to students.  A link is placed in Blackboard for the question and the solution.  The solution can then be released via an adaptive release rule at the Instructor's discretion.

## AJAX Grade Read and Write ##
Use these tags as convenient grade read and write back functions to the LMS in your own javascript code in templates. This tag follows standard IMS Global grade read/write security protocol. See [IMS LTI Implementation guide -> 4.3 Security for application/xml Messages](http://www.imsglobal.org/specs/ltiv1p1/implementation-guide).  

Strangely enough, the data attributes are worded as if something will go wrong, but usually you only need to check that `data.codeMajor === 'success'` and `data.description` for output.  

The sample template for this is `examples/sample_grade_readwrite_LMS.html`.

Usage:
```
<script>
    {grade_write_js}
        /* your javascript here to handle data
         data.description, data.severity, data.codeMajor */
    {/grade_write_js}

    {grade_read_js}
        /* your javascript here to handle data, options for data are:
        data.description, data.codeMajor, data.resultScore, data.severity */
    {/grade_read_js}
</script>
```

## Settings ##


```
{general_settings_form}
```
This tag provides a settings form for instructors to turn plugins and features on and off, it can be restricted to certain users.

## Gradebook User and Group import ##
This extension runs automatically once added and will import all users from the LMS after it is provided with the Instructor's password. The password is encryped using the [Defuse encryption library](https://github.com/defuse/php-encryption)

### Config File ###
The gradebook import and group functions require some URIs to Blackboards services.  These are set in the config.php file located here:
```
cd EE install directory/system/user/addons/learning_tools_integration/config
```

# Installation of APEG (Adaptive Peer Evaluation for Groups)

## Requirements

* WebServer with PHP 5 or later
* mySQL Database version 5.5 or later

* Git version control system

## Steps

1. Download and install a webserver (Apache is recommended), PHP 7+, mySQL 5.5+, git and [ExpressionEngine Core CMS](https://store.ellislab.com/#ee-core)

    _There are countless resources on the web to install the above.  The suggested items are all open source or free_

2. Clone and Install the ExpressionEngine Modules
  The most up to date version of the application is available at Github and comes in two parts at the [BOLDLab organization](https://github.com/BOLDLab).   

3. `git clone` the learning_tools_integration and lti_peer_assessment repos into the addons folder of the EE distribution. Then follow the normal install process for addons in EE.

