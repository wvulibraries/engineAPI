# List and Form Objects

## General Guidelines

These objects should be 'self contained.' that is, if something requires a piece of javascript or some external library, it should (by default) be included as needed. These settings should be able to be overridden so that newer or different options can be used by the developer. 

Be sure to check the original list object for options. I have not listed all possible options here, such as "deleteBox" and "deleteBoxLeft" and many other options for building and rendering the forms. Most all of these options are globals in the listManagement object. Where appropriate, use the same defaults. They work well. 

Be sure to use dependency injection to make testing the modules/objects easier.

Be sure that all the needed curly tag replacements are in place. 

## Callbacks

We should be able to handle all of these situations with sane default methods. However, there are cases where the default methods will not be sufficient enough, but we still want to take advantage of the default form modules available in engine. 

1. When an insert form is submitted
1. When an edit form is submitted
1. When the submission is successful
1. When the submission fails
1. On each error
1. When there is a warning (?)

## form submitter 

handles the actual submission of a form. 

should be able to be used independently of the form creator. That is, i should be able to build an object and a $_POST and have the form submitter submit it and provide all relevant feedback. 

### Methods

#### insert($ID=NULL)

submission of a single object, update when ID is provided

#### update()

submission of the edit table (multiple objects)

#### delete($ID)

Should be able to handle linked data as well. 

If I delete, for example, a subject heading. and that subject heading is being used else where, it should warn me of all the potential issues and ask how to proceed. If the user wants to proceed should remove those linked references. 

accomplishing this may need the form Builder module to have some additional 'smarts' beyond the "matchOn" options. If possible, I would like to put everything into their and have the form builder object know about all the linked tables based on the match on's. Perhaps "match on" needs to be renamed to "linked information" or somethine similar. 

## Form Builder

Responsible for drawing and rendering the form and all information back to the browser

little to no HTML should be in the code itself. 

* Should be able to use templates.
	* Templates need a repeat block to handle where to put things. 
	* Could have multiple repeat blocks. 
* The default build, without a template specified should be the same as it is now
	* Labels on the right, aligned
	* fields on the left, aligned
	* Doesn't have to use a table, 
	* The default build should use a template
* When appropriate, use HTML5 attributes (such as for "required"), but don't rely on those for validation. Check on submit as well. 
	* For options like "pladeholder" assume an HTML5 web browser and don't worry about backwards compatibility

* Should be able to handle ajax submissions and responses if these are desired by the developer 
* needs to be able to work inside a modal box
* needs to draw change/blur/other events in jquery after the form itself is rendered completely. (???? should this be handled by the module or by the developer elsewhere)

### Methods

#### displayInsertForm($formAction=NULL)

displays the insert form

$formAction is the action URL, if NULL use current URL

#### displayEditTable($formAction=NULL)

$formAction is the action URL, if NULL use current URL

The edit table should show a set of fields in a strip. these fields should be editable (if appropriate), and submitted as a whole. 

Should be able to click somewhere (if defined) to 'drop down' the full edit for for the document.

#### templates

See the RSS template for an example of repeat blocks if needed.

Simple Example:

	{formBuilder var="formTitle"}

	{formBuilder var="formBegin"}

	{formBuilder display="fields"}

	{formBuilder var="formEnd"} 

Example with Field Sets:

	{formBuilder var="formTitle"}

	{formBuilder var="formBegin"}

	<!-- this will add the fieldset and the legend automatically -->
	{formBuilder fieldsetBegin="myFieldSetName"}
	{formBuilder display="myFieldSetName"}
	{formBuilder fieldsetEnd="myFieldSetName"}

	{formBuilder var="formEnd"} 

There is no reason this couldn't be done by hand if something more complicated was needed

	{formBuilder var="formTitle"}

	{formBuilder var="formBegin"}

	<!-- this will add the fieldset and the legend automatically -->
	<div id="foo">
	{formBuilder display="myFieldSetName"}
	</div>

	<div id="bar">
	{formBuilder display="myOtherFieldSetName"}
	</div>

	{formBuilder var="formEnd"} 

## Field Builder

builds the form objects that the submitter and creator will use

### methods

#### addField($field)

adds a field to the object. Returns TRUE on success, False otherwise

1. Duplicate field names are not allowed
1. Duplicate IDs are not allowed
1. Duplicate field Labels are not allowed

##### $field : type array

name
:the name of the input

Label
:The label for the input

size
:the size attribute on the input

allowDuplicates
:can duplicates appear in the database for this field?

labelCSS
:CSS to be applied to the element in a local "style" attribute

labelID
:custom ID to be applied to the label

labelClass
:Class name to append to the class of the label
	
	* Every label should already have a class of "engineAPIformBuilderLabel"
	* Every class of a required fiels should also have a class of "required"

fieldcss
: CSS to be applied to the field in a local style attribute

fieldID
: the Custom ID to use for a input field. Be sure that this matches the "for" .. this should be ignored in edit tables

required
:Sets the required flag on the element. Also checks it on submit

type
:the input type. Text, radio, select, hidden, etc ... See the old list object for 'custom' types, such as 'wysiwyg' and 'multiselect'. Also take HTML5 types with fall-backs

readonly
:field is readonly. when submitting the original value should be used instead of what is submitted.

disabled
:field is disabled. when submitting data should be ignored entirely

selectValues
: options for a select box
: should be an array of arrays and support values, labels, and option groups

value
: the value to put into the value attribute

validate
: which validation method to use from the validation module. string(built-in validation) or callback(custom validation)

linkedTo
: used to join linking tables. 
: should be an array with
	
	* field : This is the field that we are pulling
	* table : this is the table we are pulling the field from
	* key : this is the key for the table we are pulling the field from. This is the "known" value that will be replaced in the rendered form
	* relationship : Relationship rules (cascade, setNull, ignore, fail, etc)

original
: *removed from options* Will be done implicitly in session

placeholder
: placeholder text.

disableStyling
: If TRUE, disable rendering of label and field CSS (defaults to FALSE)

help
: help for the field. Should be an array. Check the current list object to make sure all values are accounted for
	
	* position : beforeLabel (default), afterLabel, beforeInput, afterInput
	* type : modal, newWindow, hover
	* value : the value to display. can be plain text, from a file (/some/where/open/me.txt), or a url. File and url should be included as is and assumed to be safe. 

dragAndDrop
: Boolean. Is this field able to be dragged and dropped for ordering purposes in the browser?

showInEditStrip
: If true, this field is shown in the "edit strip" that is displayed in the edit table.

fieldSet
: If this is part of a fieldSet, the fieldset name should be here. This should be what needs to be displayed in the "legend"

sortOrder
: The order that this field should appear. Everything that does not have a sort order should be placed after fields that do, and added in the order in which they were added the the object

labelMetadata
: Array of key->value pairs of attributes to include on the label
: * Special 'data' element to contain key->value pairs for data-*="" attributes
: * Exclude functional attributes such as disabled and readonly (These must be done explicitly in the general field options)

fieldMetadata
: Array of key->value pairs of attributes to include on the <input> tag
: * Special 'data' element to contain key->value pairs for data-*="" attributes
: * Exclude functional attributes such as disabled and readonly (These must be done explicitly in the general field options)

#### removeField(string $fieldName)

removes a field from the object with name $fieldName

Returns TRUE on success

#### modifyField($fieldName,$option,$value)

modifies an existing field. 

$fieldName is the name of the field. $option is the index. $value is the new value that should be assigned

#### modifyAllField($option,$value)

*same as modifyField except implicity applies modification to all fields*

#### getField($name)

Returns the field definition for the given field, or NULL if no field defined

#### getFieldNames()

Returns array of all fieldNames

