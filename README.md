# README #

### LTI Module for ExpressionEngine 3 ###

#Version 2.1#

* Provides template tags, grade write and grade read functions for integration into other systems using the Learning Tools Interoperability™ (LTI) protocol. LTI is used for cross domain access to external learning tools in the leading learning management systems (LMSs) and virtual learning environments (VLEs) used by educational institutions, such as Moodle, Blackboard Learn and Instructure Canvas. Once 'launched' from the LMS the user is automatically logged in using oAuth 1.0 and added to the Members table based on the LTI context sent from the LMS. Control panel access allows addition of an unlimited amount of LTI providers, each on different template url segments on the site. This module and extension use IMS Global's LTI Specification version 1.0.

Developed at the Centre for Teaching and Learning at the University of Newcastle (UoN), this tool is in constant development and used in on-line Blackboard courses.

It has had rudimentary testing in Moodle, further development is desired in this direction. 

### Getting started ###

* Get a copy of [ExpressionEngine Core at Ellislabs](https://store.ellislab.com/#ee-core) (note this is limited to 3 back-end administrative and content development users, you will need to pay for the full version if you need more administrators).
* [Download the zip file of the latest release at devot:ee](https://devot-ee.com/add-ons/learning-tools-integration)
* Follow the instructions at the above link.
* The extension_hooks directory will soon be moved to a new repository as this is where most of the LMS specific code is housed.  This is where you can build specific extensions to suit your LMS. 

#Tools available for Blackboard Learn as extension hooks#

##User and Group Import##
**EE Tags provided**
*Allows manual and automated import of Users and/or Groups using CSV or with secure authentication to Blackboard Learns Gradebook JSON export REST URI.*

#Blackboard Rubrics#
**EE Tags provided**
```
#!html

{upload_blackboard_rubric}
{render_blackboard_rubric}
```
Provides tags that allow import and display of rubric ZIP archives exported from Blackboard learn, provides tags for viewing the Rubrics.  These can be used with the Peer Assessment plugin below.*

#Secure Resource Delivery#
**EE Tags provided**
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

*This extension provides tags to allow the setup of randomly assigned question and answer resources to students.  A link is placed in Blackboard for the question and the solution.  The solution can then be released via an adaptive release rule at the Instructor's discretion.*

#Tools available as ExpressionEngine(EE) Plugins#

* [Peer Review Module](https://bitbucket.org/sijpkes/lti-peer-assessment)
*This EE plugin is in constant development as I get more interest from academics here at UoN.  Pull this one from BitBucket via the above link.*

**This add-on has only been tested in Blackboard Learn.***

***REQUIRES: all the above extension hooks to run***


### Contribution guidelines ###

* Contributors are NEEDED, so if you're keen on building this tool further please contact [Paul Sijpkes](mailto:paul.sijpkes@newcastle.edu.au).