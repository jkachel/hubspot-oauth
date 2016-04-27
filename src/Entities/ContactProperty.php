<?php
	/**
	 * Created by PhpStorm.
	 * User: jkachel
	 * Date: 4/25/16
	 * Time: 9:47 AM
	 */

	namespace HubspotOauth\Entities;

	use Carbon\Carbon;
	use HubspotOauth\Authenticate\Authenticate;

	class ContactProperty
	{
		public $name; /**< Updateable via API **/
		public $label; /**< Updateable via API **/
		public $description; /**< Updateable via API **/
		public $groupName; /**< Updateable via API **/
		public $type; /**< Updateable via API **/
		public $fieldType; /**< Updateable via API **/
		public $formField; /**< Updateable via API **/
		public $displayOrder; /**< Updateable via API **/
		public $options; /**< Updateable via API **/
		public $readOnlyValue;
		public $readOnlyDefinition;
		public $hidden;
		public $mutableDefinitionNotDeletable;
		public $favorited;
		public $favoritedOrder;
		public $calculated;
		public $externalOptions;
		public $displayMode;
		public $deleted;
		/**
		 * A note about the below: These kinds of records do not have actual IDs. The PK on this is the name field.
		 * Because of that, determining which API endpoint to use to save or create is problematic. If this particular
		 * property was loaded (via constructor or the "all" method), this flag will be set to false. Otherwise, it will
		 * be set to true.
		 *
		 * Do not modify this. Doing so will either make this try to update a property that does not exist (which will
		 * likely fail) or will have it attempt to create a new property that mirrors one that's in there already.
		 */
		public $isNew; /**< True if this is new, false if not. **/
		protected $api; /**< The Authenticate object to run queries against. **/

		/** @brief Sets up the ContactProperty object and optionally loads an existing property.
		 *
		 * Parameters:
		 * $api 		The HubspotOauth\Authenticate\Authenticate object to query (i.e. the connection to use)
		 * $name		The contact property name to load, or false for new. (This is the "name" field in the API docs.)
		 *
		 */

		public function __construct($api, $name = false) {
			$this->api = $api;

			if($name == false) {
				foreach(get_object_vars($this) as $var) {
					$this->{$var} = '';
				}

				$this->isNew = true;
			} else {
				$result = $api->call('get', '/contacts/v2/properties/named/' . $name);

				if(count($result) == 0) {
					foreach(get_object_vars($this) as $var) {
						$this->{$var} = '';
					}

					$this->isNew = true;
				} else {
					foreach($result[0] as $key => $value) {
						$this->{$key} = $value;
					}

					$this->isNew = false;
				}
			}
		}

		public function save() {
			if(strlen($this->name) == 0) {
				throw new \Exception('Need at least a name.');
			}

			$data = ['json' => [
				'name' => $this->name,
				'label' => $this->label,
				'description' => $this->description,
				'groupName' => $this->groupName,
				'type' => $this->type,
				'fieldType' => $this->fieldType,
				'formField' => $this->formField,
				'displayOrder' => $this->displayOrder,
				'options' => $this->options
			]];

			if($this->isNew) {
				$result = $this->api->call('post', '/contacts/v2/properties/', $data);
			} else {
				$result = $this->api->call('put', '/contacts/v2/properties/named/' . $this->name, $data);
			}

			return true;
		}

		public function delete() {

		}

		public static function all(Authenticate $api) {
			$response = $api->call('get', '/contacts/v2/properties/');

			$result = [];

			foreach($response as $property) {
				$tmpRes = new ContactProperty($api, false);

				foreach($property as $key => $value) {
					$tmpRes->{$key} = $value;
				}

				$tmpRes->isNew = false;

				$result[] = $tmpRes;
			}

			return $result;
		}
	}