<?php

if (!defined('__IN_SYMPHONY__'))
    die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(TOOLKIT . '/class.field.php');

/**
 *
 * Field class for Automatic Slug Creation
 * @author Sarah Kemp (thesarahkemp@gmail.com)
 *
 */
class FieldSlug_Field extends Field {
    /**
     *
     * Name of the field table
     * @var string
     */

    const FIELD_TBL_NAME = 'tbl_fields_slug_field';

    private $prefixes = array('Table' => 'table-', 'Entry' => 'entry-');

    /* -------------------------------------------------------------------------
      Utilities:
      ------------------------------------------------------------------------- */

    private function __applyValidationRules($data) {
        $rule = '/^[a-z0-9-]+$/';
        return ($rule ? General::validateString($data, $rule) : true);
    }

    /**
     *
     * Constructor for the Field object
     * @param mixed $parent
     */
    public function __construct() {
        // call the parent constructor
        parent::__construct();
        // set the name of the field
        $this->_name = __('Slug Field');
        // permits to make it required
        $this->_required = true;
        // permits the make it show in the table columns
        $this->_showcolumn = true;
        // set as required by default
        $this->set('required', 'yes');
    }

    public function isSortable() {
        return true;
    }

    public function canFilter() {
        return true;
    }

    public function canImport() {
        return false;
    }

    public function canPrePopulate() {
        return false;
    }

    public function mustBeUnique() {
        return ($this->get('unique') == 'yes');
    }

    public function allowDatasourceOutputGrouping() {
        return false;
    }

    public function requiresSQLGrouping() {
        return false;
    }

    public function allowDatasourceParamOutput() {
        return true;
    }

    /*     * ********* INPUT AND FIELD *********** */

    /**
     *
     * Validates input
     * Called before <code>processRawFieldData</code>
     * @param $data
     * @param $message
     * @param $entry_id
     */
    public function checkPostFieldData($data, &$message, $entry_id = NULL) {
        $message = NULL;

        if (is_array($data) && isset($data['value'])) {
            $data = $data['value'];
        }

        if ($this->get('required') == 'yes' && strlen($data) == 0) {
            $message = __('‘%s’ is a required field.', array($this->get('label')));
            return self::__MISSING_FIELDS__;
        }

        if (!$this->__applyValidationRules($data)) {
            $message = __('‘%s’ contains invalid data. Stick to letters, numbers, and dashes (-).', array($this->get('label')));
            return self::__INVALID_FIELDS__;
        }

        return self::__OK__;
    }

    /**
     *
     * Process entries data before saving into database.
     *
     * @param array $data
     * @param int $status
     * @param boolean $simulate
     * @param int $entry_id
     *
     * @return Array - data to be inserted into DB
     */
    public function processPostFieldData($data, &$message, $entry_id = NULL) {
        $status = self::__OK__;

        if (strlen(trim($data)) == 0)
            return array();

        $result = array(
            'value' => $data
        );

        $result['handle'] = Lang::createHandle($result['value']);

        return $result;
    }

    /**
     * This function permits parsing different field settings values
     *
     * @param array $settings
     * 	the data array to initialize if necessary.
     */
    public function setFromPOST(Array $settings = array()) {

        // call the default behavior
        parent::setFromPOST($settings);

        // declare a new setting array
        $new_settings = array();

        // always display in table mode
        $new_settings['show_column'] = $settings['show_column'];

        $new_settings['field_to_mimic'] = $settings['field_to_mimic'];

        // save it into the array
        $this->setArray($new_settings);
    }

    /**
     *
     * Validates the field settings before saving it into the field's table
     */
    public function checkFields(Array &$errors, $checkForDuplicates) {
        parent::checkFields($errors, $checkForDuplicates);

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     *
     * Save field settings into the field's table
     */
    public function commit() {

        // if the default implementation works...
        if (!parent::commit())
            return FALSE;

        $id = $this->get('id');

        // exit if there is no id
        if ($id == false)
            return FALSE;

        // declare an array contains the field's settings
        $settings = array();

        // the field id
        $settings['field_id'] = $id;

        // the related fields handles
        $settings['field_to_mimic'] = $this->get('field_to_mimic');


        // DB
        $tbl = self::FIELD_TBL_NAME;

        Symphony::Database()->query("DELETE FROM `$tbl` WHERE `field_id` = '$id' LIMIT 1");

        // return if the SQL command was successful
        return Symphony::Database()->insert($settings, $tbl);
    }

    /*     * ****** DATA SOURCE ******* */

    /**
     * Appends data into the XML tree of a Data Source
     * @param $wrapper
     * @param $data
     */
    public function appendFormattedElement(&$wrapper, $data) {
        $slug = new XMLElement($this->get('element_name'));
	$slug->setAttribute('field-id', $this->get('id'));
        $slug->setValue($data['value']);
        $wrapper->appendChild($slug);
    }

    /*     * ******** UI *********** */

    /**
     *
     * Builds the UI for the publish page
     * @param XMLElement $wrapper
     * @param mixed $data
     * @param mixed $flagWithError
     * @param string $fieldnamePrefix
     * @param string $fieldnamePostfix
     */
    public function displayPublishPanel(&$wrapper, $data = NULL, $flagWithError = NULL, $fieldnamePrefix = NULL, $fieldnamePostfix = NULL) {
        $value = General::sanitize(isset($data['value']) ? $data['value'] : null);
        $label = Widget::Label($this->get('label'));
        if ($this->get('required') != 'yes')
            $label->appendChild(new XMLElement('i', __('Optional')));
        $field = Widget::Input('fields' . $fieldnamePrefix . '[' . $this->get('element_name') . ']' . $fieldnamePostfix, (strlen($value) != 0 ? $value : NULL));
        $field->setAttribute('data-field-to-mimic', $this->get('field_to_mimic'));
        $field->setAttribute('data-slug-field', $this->get('element_name'));

        if ($data && $flagWithError == NULL) {
            $field->setAttribute('style', 'display:none;');
            $field->setAttribute('readonly', 'readonly');
            $anchor = Widget::Anchor(
                            $data['value'], is_null($data['value']) ? '#' : (string) '/'.$data['value']
            );
            $label->appendChild($anchor);
        }

        $label->appendChild($field);

        if ($flagWithError != NULL)
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        else
            $wrapper->appendChild($label);
    }


    /**
     *
     * Builds the UI for the field's settings when creating/editing a section
     * @param XMLElement $wrapper
     * @param array $errors
     */
    public function displaySettingsPanel(&$wrapper, $errors = NULL) {

        /* first line, label and such */
        parent::displaySettingsPanel($wrapper, $errors);

        $mimic_wrap = new XMLElement('div', NULL, array('class' => 'slug_field'));
        $mimic_wrap->appendChild($this->createInput('Handle of existing field to mimic', 'field_to_mimic', $errors));
        $wrapper->appendChild($mimic_wrap);

        $div = new XMLElement('div', NULL, array('class' => 'two columns'));
        $this->appendRequiredCheckbox($div);
        $this->appendShowColumnCheckbox($div);
        $wrapper->appendChild($div);
    }

    private function createInput($text, $key, $errors = NULL) {
        $order = $this->get('sortorder');
        $lbl = new XMLElement('label', __($text), array('class' => 'column'));
        $input = new XMLElement('input', NULL, array(
            'type' => 'text',
            'value' => $this->get($key),
            'name' => "fields[$order][$key]"
        ));
        $input->setSelfClosingTag(true);

        $lbl->prependChild($input);

        //var_dump($errors[$key]);

        if (isset($errors[$key])) {
            $lbl = Widget::wrapFormElementWithError($lbl, $errors[$key]);
        }

        return $lbl;
    }

    private $tableValueGenerated = FALSE;

    /**
     *
     * Build the UI for the table view
     * @param Array $data
     * @param XMLElement $link
     * @return string - the html of the link
     */
    public function prepareTableValue($data, XMLElement $link = NULL) {
        return $data['value'];
    }

    /**
     *
     * Return a plain text representation of the field's data
     * @param array $data
     * @param int $entry_id
     */
    public function preparePlainTextValue($data, $entry_id = null) {
        return (string) $data['value'];
    }

    /**
     *
     * This function allows Fields to cleanup any additional things before it is removed
     * from the section.
     * @return boolean
     */
    public function tearDown() {
        // do nothing
        return false;
    }

    /*     * ******** SQL Data Definition ************* */

    /**
     *
     * Creates table needed for entries of invidual fields
     */
    public function createTable() {

        return Symphony::Database()->query(
                        "CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
                                        `value` VARCHAR(255) NOT NULL,
					PRIMARY KEY  (`id`),
					KEY `entry_id` (`entry_id`)
				) TYPE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );

        //return FALSE;
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public static function createFieldTable() {

        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `$tbl` (
					`id` 				int(11) unsigned NOT NULL auto_increment,
					`field_id` 			int(11) unsigned NOT NULL,
					`field_to_mimic`		varchar(255) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public static function deleteFieldTable() {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
				DROP TABLE IF EXISTS `$tbl`
			");
    }

}