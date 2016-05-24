
<div class="locationPane" id="rub_onExitClose" data-input_id="<?= $input_id ?>" data-pre_pop="<?= $pre_pop ?>">

		<div id="contentPanel" class="contentPaneWide ">

			<div id="content" class="contentBox ">

				<div id="pageTitleDiv" class="pageTitle clearfix ">


					<div id="pageTitleBar" class="pageTitleIcon" tabindex="0">
						<img src="/images/ci/sets/set01/editpage_on.gif" alt=""
							id="titleicon">
						<h1 id="pageTitleHeader" tabindex="-1">
							<span id="pageTitleText">Rubric</span>
						</h1>
						<span id="_titlebarExtraContent" class="titleButtons"></span>
					</div>


					<div id="helpPageTitle" tabindex="0" class="helphelp">
						<p class="helphelp">
							A rubric lists grading criteria that instructors use to evaluate
							student work. Your instructor linked a rubric to this item and
							made it available to you. Select <strong>Grid View</strong> or <strong>List
								View</strong> to change the rubric's layout.
						</p>
					</div>

				</div>

				<div class="container clearfix" id="containerdiv">
					<h2 class="hideoff">Content</h2>
					<form action="">

						<div class="rubricPopupContainer">
							<div class="rubricDetails">
								<div class="searchbar">
									<div class="clearfix gradeInfoHeader clearfloats">
										<div id="rubricDetailsDiv" class="u_floatThis-left">
											<h3>
												Name:&nbsp;<span>Sample Rubric</span>
                                                <em style='color: darkblue'> &rarr; assess <strong><?= $username ?></strong>'s performance below.</em>
											</h3>
											<div>
												<h3>
													Description:&nbsp;<span>A description here</span>
												</h3>
											</div>
										</div>
										<h3 class="u_floatThis-right changeRubricContainer"></h3>
										<div class="u_floatThis-right navStatusButtons">
											<div class="taskbuttondiv_wrapper">
												<p class="taskbuttondiv" id="bottom_submitButtonRow">
													<input class="submit button-1" name="bottom_Exit"
														type="submit" value="<?= $exit_button_value ?>">

												</p>
											</div>
										</div>
									</div>
								</div>

								<div class="rubricControlContainer">
									<ul id="containerTabs" class="containerTabs clearfix"
										role="tablist">
										<li id="gridViewTab" role="tab"
											aria-controls="contentAreaBlock0" class="active" tabindex="0"
											aria-selected="true"><a href="#">Grid View</a></li>
										<li id="listViewTab" role="tab"
											aria-controls="contentAreaBlock1" class="" tabindex="-1"
											aria-selected="false"><a href="#">List View</a></li>
									</ul>
									<div id="contentAreaBlock0" class="contentAreaBlock"
										role="tabpanel" aria-labelledby="gridViewTab">
										<?= $grid ?>
									</div>
									<div id="contentAreaBlock1" class="contentAreaBlock"
										style="display: none" role="tabpanel"
										aria-labelledby="listViewTab">
										<?= $list ?>
									</div>
								</div>

								<div class="searchbar">
									<div class="clearfix gradeInfoHeader clearfloats">
										<div class="u_floatThis-left">
											<h3>
												Name:<span>Sample Rubric</span>
											</h3>
											<div>
												<h3>
													Description:<span>A description here</span>
												</h3>
											</div>
										</div>
										<h3 class="u_floatThis-right changeRubricContainer"></h3>
										<div class="u_floatThis-right navStatusButtons">
											<div class="taskbuttondiv_wrapper">
												<p class="taskbuttondiv" id="bottom_submitButtonRow">
													<input class="submit button-1" name="bottom_Exit"
														type="submit" value="<?= $exit_button_value ?>">

												</p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>


<script type="text/javascript">
	<?= $js_controls ?>
	<?= $hide_scores ?>
</script>
