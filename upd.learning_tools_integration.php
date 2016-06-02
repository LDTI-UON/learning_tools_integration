<?php if (! defined ( 'BASEPATH' ))
					exit ( 'No direct script access allowed' );

				/**
				 * ExpressionEngine - by EllisLab
				 *
				 * @package ExpressionEngine
				 * @author ExpressionEngine Dev Team
				 * @copyright Copyright (c) 2003 - 2011, EllisLab, Inc.
				 * @license http://expressionengine.com/user_guide/license.html
				 * @link http://expressionengine.com
				 * @since Version 2.0
				 * @filesource
				 *
				 */

				// ------------------------------------------------------------------------

				/**
				 * EE Learning Tools Integration Module Install/Update File
				 *
				 * @package ExpressionEngine
				 * @subpackage Addons
				 * @category Module
				 * @author Paul Sijpkes
				 * @link http://sijpkes.site11.com
				 */
				class Learning_tools_integration_upd {
					public $version = '2.23';
					public $mod_class = 'Learning_tools_integration';
					private $EE;

					/**
					 * Constructor
					 */
					public function __construct() {
						// ee() =& get_instance();
					}

					// ----------------------------------------------------------------

					/**
					 * Installation Method
					 *
					 * @return boolean TRUE
					 */
					public function install() {
						$mod_data = array (
								'module_name' => $this->mod_class,
								'module_version' => $this->version,
								'has_cp_backend' => "y",
								'has_publish_fields' => 'n'
						);

						ee ()->db->insert ( 'modules', $mod_data );

						ee ()->load->dbforge ();

						$fields = array (
								'id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => 5,
										'null' => FALSE,
										'auto_increment' => TRUE
								),
								'oauth_consumer_key' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'secret' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => TRUE
								),
								'name' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => TRUE
								),
								'context_id' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => TRUE
								),
								'url_segment' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => TRUE
								),
								'created_at' => array (
										'type' => 'DATETIME',
										'null' => FALSE,
								),
								'updated_at' => array (
										'type' => 'DATETIME',
										'null' => FALSE,
								)
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->create_table ( 'blti_keys', TRUE );

						$table_name = ee ()->db->dbprefix ( "blti_keys" );
						$sql = "CREATE UNIQUE INDEX url_segment_index ON $table_name(url_segment)";
						ee ()->db->query ( $sql );

						$fields = array (
								'id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => TRUE
								),
								'user_id' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => TRUE,
								),
								'username' => array (
										'type' => 'VARCHAR',
										'constraint' => 50,
										'null' => TRUE
								),
								'member_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE
								),
								'session_id' => array (
										'type' => 'VARCHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'context_id' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'context_label' => array (
										'type' => 'CHAR',
										'constraint' => '25',
										'null' => FALSE
								),
								'course_name' => array (
										'type' => 'VARCHAR',
										'constraint' => '80',
										'null' => FALSE
								),
								'ext_lms' => array (
										'type' => 'CHAR',
										'constraint' => '48',
										'null' => FALSE
								),
								'tool_consumer_instance_id' => array (
										'type' => 'INT',
										'constraint' => '11',
										'null' => FALSE
								),
								'tool_consumer_instance_name' => array (
										'type' => 'CHAR',
										'constraint' => '100',
										'null' => FALSE
								),
								'is_instructor' => array (
										'type' => 'TINYINT',
										'constraint' => '1',
										'null' => FALSE,
										'default' => '0'
								),
								'session_data' => array (
										'type' => 'VARCHAR',
										'constraint' => '5000',
										'null' => TRUE
								),
                                'last_launched_on TIMESTAMP DEFAULT 0',
                                'imported_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->create_table ( 'lti_member_contexts', TRUE );

						$table_name = ee ()->db->dbprefix ( "lti_member_contexts" );
						$sql = "CREATE UNIQUE INDEX member_context_guid_index ON $table_name(member_id, context_id(10), tool_consumer_instance_id)";
						ee ()->db->query ( $sql );

						$fields = array (
								'id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => TRUE
								),
								'internal_context_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => TRUE
								),
								'uploader_internal_context_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE
								),
								'course_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE
								),
								'file_name' => array (
										'type' => 'VARCHAR',
										'constraint' => '85',
										'null' => FALSE
								),
								'salt' => array (
										'type' => 'VARCHAR',
										'constraint' => '10',
										'null' => FALSE
								),
								'display_name' => array (
										'type' => 'VARCHAR',
										'constraint' => '85',
										'null' => FALSE
								),
								'base_name' => array (
										'type' => 'VARCHAR',
										'constraint' => '12',
										'null' => FALSE
								),
								'type' => array (
										'type' => 'CHAR',
										'constraint' => '1',
										'default' => 'P'
								),
                              'uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP',

						)
						;

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->create_table ( 'lti_member_resources', TRUE );

						$fields = array (
								'id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => TRUE
								),
								'member_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => FALSE
								),
								'internal_context_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE
								),
								'group_no' => array (
										'type' => 'VARCHAR',
										'constraint' => '15',
										'null' => FALSE
								),
								'group_name' => array (
										'type' => 'VARCHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'group_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '9',
										'null' => FALSE
								),
								'context_id' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'tool_consumer_instance_id  ' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '9',
										'null' => FALSE
								)
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->create_table ( 'lti_group_contexts', TRUE );

						$table_name = ee ()->db->dbprefix ( "lti_group_contexts" );
						$sql = "CREATE INDEX member_internal_index ON $table_name(member_id, internal_context_id)";
						ee ()->db->query ( $sql );

						$fields = array (
								'problem_prefix' => array (
										'type' => 'VARCHAR',
										'constraint' => '20',
										'null' => FALSE
								),
								'solution_prefix' => array (
										'type' => 'VARCHAR',
										'constraint' => '20',
										'null' => FALSE
								),
                                'enable_group_import' => array(
                                     'type' => 'TINYINT',
                                     'constraint' => '1',
                                     'null' => FALSE
                                ),
                                'plugins_active' => array (
										'type' => 'VARCHAR',
										'constraint' => '500',
								),
								'course_key' => array (
										'type' => 'VARCHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'institution_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE
								),
                                'gradebook' => array (
										'type' => 'LONGTEXT',
										'null' => TRUE,
								),
                                'last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL',
						);


						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'course_key', TRUE );
						ee ()->dbforge->add_key ( 'institution_id', TRUE );
						ee ()->dbforge->create_table ( 'lti_instructor_settings', TRUE );

                        $fields = array (
								'id' => array (
										'type' => 'INT',
										'constraint' => '11',
										'null' => FALSE,
                                        'auto_increment' => TRUE,
								),
                                'institution_id' => array (
										'type' => 'INT',
										'constraint' => '11',
										'null' => FALSE,
								),
								'context_id' => array (
										'type' => 'CHAR',
										'constraint' => '255',
										'null' => FALSE
								)
						);

                        ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
                        ee ()->dbforge->create_table ( 'lti_course_contexts', TRUE );

						$fields = array (
								'id' => array (
										'type' => 'INT',
										'constraint' => '11',
										'null' => FALSE,
								),
								'guid' => array (
										'type' => 'VARCHAR',
										'constraint' => '250',
										'null' => FALSE
								)
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'guid', TRUE );
						ee ()->dbforge->create_table ( 'lti_tool_consumer_instances', TRUE );

						$fields = array (
								'id' => array (
										'type' => 'INT',
										'constraint' => '11',
										'null' => FALSE,
                                     'auto_increment' => TRUE
								),
								'name' => array (
										'type' => 'VARCHAR',
										'constraint' => '250',
										'null' => FALSE
								)
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->create_table ( 'lti_institutions', TRUE );

						/* bug fix function it's DRY :-) */
						$this->create_table('lti_instructor_credentials');

						$fields = array (
								'id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => TRUE
								),
								'course_id' => array (
										'type' => 'MEDIUMINT',
										'constraint' => '5',
										'null' => FALSE,
										'auto_increment' => FALSE
								),
								'resource_link_id' => array (
										'type' => 'VARCHAR',
										'constraint' => '255',
										'null' => FALSE
								),
								'rubric_id' => array (
										'type' => 'VARCHAR',
										'constraint' => '18',
										'null' => FALSE
								)
						);

						ee ()->dbforge->add_field ( $fields );
						ee ()->dbforge->add_key ( 'id', TRUE );
						ee ()->dbforge->add_key ( 'resource_link_id' );
						ee ()->dbforge->create_table ( 'lti_course_link_resources', TRUE );

                        $course_table = ee ()->db->dbprefix("lti_course_link_resources");
						$sql = "ALTER TABLE $course_table ADD INDEX ( resource_link_id(10) )";

						ee()->db->query ( $sql );

						// add ajax actions
						$data = array (
								'class' => $this->mod_class,
								'method' => 'message_preference'
						);
						ee ()->db->insert ( 'actions', $data );

						$data = array (
								'class' => $this->mod_class,
								'method' => 'save_user_grade'
						);
						ee ()->db->insert ( 'actions', $data );

						$data = array (
								'class' => $this->mod_class,
								'method' => 'read_user_grade'
						);
                        ee ()->db->insert ( 'actions', $data );

						return TRUE;
					}

					// ----------------------------------------------------------------

					/**
					 * Uninstall
					 *
					 * @return boolean TRUE
					 */
					public function uninstall() {

                        /* drop tables and remove module ref */
						$mod_id = ee ()->db->select ( 'module_id' )->get_where ( 'modules', array (
								'module_name' => $this->mod_class
						) )->row ( 'module_id' );

						ee ()->db->where ( 'module_id', $mod_id )->delete ( 'module_member_groups' );

						ee ()->db->where ( 'module_name', $this->mod_class )->delete ( 'modules' );

						ee ()->load->dbforge ();
						ee ()->dbforge->drop_table ( 'blti_keys' );
						ee ()->dbforge->drop_table ( 'lti_group_contexts' );
						ee ()->dbforge->drop_table ( 'lti_instructor_settings' );
						ee ()->dbforge->drop_table ( 'lti_instructor_credentials' );
						ee ()->dbforge->drop_table ( 'lti_member_contexts' );
						ee ()->dbforge->drop_table ( 'lti_tool_consumer_instances' );
						ee ()->dbforge->drop_table ( 'lti_institutions' );
						ee ()->dbforge->drop_table ( 'lti_member_resources' );

                        ee ()->dbforge->drop_table ( 'lti_course_contexts' );
                        ee ()->dbforge->drop_table ( 'lti_course_link_resources' );

						ee ()->db->delete ( 'actions', array (
								'class' => $this->mod_class,
								'method' => 'message_preference'
						) );

						return TRUE;
					}

					// ----------------------------------------------------------------

					/**
					 * Module Updater
					 *
					 * @return boolean TRUE
					 */
					public function update($current = '') {
						// Will start using this update function in versions > 2.1
						ee ()->load->dbforge ();

						if (version_compare($current, '2.23', '='))
	 					{
			 					return FALSE;
	 					}

						// bug fix
						if (version_compare($current, '2.23', '<'))
	 					{
							$fields = array(
                        'user_id' => array(
                                         'name' => 'user_id',
                                         'type' => 'CHAR',
																				 'constraint' => 255,
																				 'null' => true,
                                      ),
																);

							ee()->dbforge->modify_column('lti_member_contexts', $fields);
						}

	 					if (version_compare($current, '2.21', '<'))
	 					{
							// rebuild table with new indexes
							ee()->dbforge->drop_table('lti_instructor_credentials');

							$this->create_table('lti_instructor_credentials');
	 					}

						return TRUE;
					}

				private function create_table($table_name) {
					ee ()->load->dbforge ();
					switch($table_name) {
					case 'lti_instructor_credentials':
					// instructor credentials table
					$fields = array (
							'member_id' => array (
									'type' => 'MEDIUMINT',
									'constraint' => '5',
									'null' => FALSE,
									'auto_increment' => FALSE
							),
							'context_id' => array (
									'type' => 'CHAR',
									'constraint' => '255',
									'null' => FALSE
							),
							'resource_link_id' =>
									array ('type' => 'CHAR',
												'constraint' => '25',
												'null' => FALSE,
							),
							'disabled' => array (
									'type' => 'TINYINT',
									'constraint' => '1',
									'null' => FALSE,
									'default' => '0'
							),
							'password' => array (
									'type' => 'VARBINARY',
									'constraint' => '255',
									'null' => TRUE
							),
							'state' => array (
									'type' => 'TINYINT',
									'constraint' => '1',
									'default' => '1',
									'null' => FALSE
							),
													'lastLogEntryTS' => array (
									'type' => 'BIGINT',
									'constraint' => '20',
							),
							'check_next' => array (
									'type' => 'TINYINT',
									'constraint' => '1',
									'default' => '1',
									'null' => FALSE
							),
							'uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
					);

					ee ()->dbforge->add_field ( $fields );
					ee ()->dbforge->add_key ( 'member_id', TRUE );
					ee ()->dbforge->add_key ( 'context_id', TRUE );
					ee ()->dbforge->add_key ( 'resource_link_id', TRUE );
					ee ()->dbforge->create_table ( 'lti_instructor_credentials', TRUE );

					break;
				}
			}
}
/* End of file upd.learning_tools_integration.php */
/* Location: /system/expressionengine/third_party/learning_tools_integration/upd.learning_tools_integration.php */
