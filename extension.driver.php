<?php

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	/*
	License: MIT
	*/
	
	require_once(EXTENSIONS . '/slug_field/fields/field.slug_field.php');
	
	class extension_slug_field extends Extension {

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendJS'
				)
			);
		}
		
		// FROM: http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
		private function startsWith($haystack, $needle) {
			$length = strlen($needle);
			return (substr($haystack, 0, $length) === $needle);
		}

		public function appendJS($context){
			$c = Administration::instance()->getPageCallback();
			$c = $c['pageroot'];
			
			// Only add when editing a section
			if ($this->startsWith($c, '/publish/')) {
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/slug_field/assets/slug_field.js',time()+1);
			}
		}
		
		
		
		/* ********* INSTALL/UPDATE/UNISTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			return FieldSlug_Field::createFieldTable();
		}
		
		
		/**
		 * Creates the table needed for the settings of the field
		 */
		public function update($previousVersion) {

                        if( version_compare($previousVersion, '1.1', '<') ){
				$fields = Symphony::Database()->fetch("SELECT `field_id` FROM `tbl_fields_slug_field`");
				foreach( $fields as $field ){
					$entries_table = 'tbl_entries_data_'.$field["field_id"];
					Symphony::Database()->query("ALTER TABLE `{$entries_table}` MODIFY `value` VARCHAR(255) default NULL");
				}
			}

			return true;
		}
		
		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall() {
			return FieldSlug_Field::deleteFieldTable();
		}
	}
