<?php
class ResourceModel {
  private $lti_object;

  function __construct($lti_instance) {
      $this->lti_object = $lti_instance;
  }

  public function total_resources() {// only counts 'problem' files, solutions not included
      if ($this -> lti_object -> isInstructor)
          return 0;

      $type = $this -> lti_object -> is_solution_request() ? 'S' : 'P';

      ee() -> db -> select('lti_member_resources.id, lti_member_resources.display_name');
      ee() -> db -> join('lti_member_contexts', 'lti_member_contexts.id = lti_member_resources.internal_context_id');
      ee() -> db -> where(array("lti_member_resources.internal_context_id" => $this -> lti_object -> internal_context_id, 'type' => $type));
      ee() -> db -> from('lti_member_resources');
      $total =   ee() -> db -> count_all_results();

      return $total;
  }
}
 ?>
