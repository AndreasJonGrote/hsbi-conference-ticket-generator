<div id="hsbi-ticket-generator">
	<div class="app">
		<div class="panel">
			<div class="header">
				<div>
					<div class="title-box">
						<span class="title-corner"></span>
						Conference Ticket Generator
					</div>
					<p class="subtitle">
						Register here for the international conference "Postphotographic Images". Use the grid-based
						pattern to customise your free ticket. Select your preferred pen and background colours, or use
						the "Random" option. Then add your name and organisation, preview your design and download your
						PNG. We will send you an email with the final details regarding the conference in mid-November.
					</p>
					<br>
					<div class="steps">
						<span class="chip active" id="step1">Step 1: Draw</span>
						<span class="chip" id="step2">Step 2: Ticket preview</span>
						<span class="chip" id="step3">Step 3: Submit</span>
						<span class="chip" id="step4">Step 4: Confirmation</span>
					</div>
					<strong id="resLabel" class="hsbi-hidden" aria-hidden="true">8 × 8</strong>
				</div>
			</div>

			<div class="grid">
				<div class="controls">
					<!-- BG before Pen before RES via CSS order -->
					<div class="control control-res blur-on-proceed">
						<label for="res">Resolution (grid)</label>
						<div class="row"><input id="res" type="range" min="2" max="16" step="1" value="8" /></div>
					</div>

					<div class="control control-pen blur-on-proceed">
						<label for="pen">Pen color</label>
						<div class="row"><input id="pen" type="color" value="#000000" /></div>
					</div>

					<div class="control control-bg blur-on-proceed">
						<label for="bg">Background color</label>
						<div class="row"><input id="bg" type="color" value="#ffffff" /></div>
					</div>

					<div class="control control-name">
						<label for="nameInput">Name <span class="muted">(required)</span></label>
						<input id="nameInput" class="text" type="text" placeholder="First and last name" value="" />
					</div>

					<div class="control control-salutation">
						<label for="salutationInput">Salutation <span class="muted">(required)</span></label>
						<input id="salutationInput" class="text" type="text" placeholder="Salutation" value="" />
					</div>

					<div class="control control-org">
						<label for="orgInput">Organization</label>
						<input id="orgInput" class="text" type="text" placeholder="Organization" value="" />
					</div>

					<div class="control control-email">
						<label for="emailInput">Email <span class="muted">(required)</span></label>
						<input id="emailInput" class="text" type="email" placeholder="you@example.com" required />
					</div>

					<div class="control control-confirm control-full">
						<div class="checkbox-row">
							<p>
								<strong class="strong">Your ticket will be sent to your email address.</strong>
							</p>
							<p>
								By submitting this form, I consent to the processing of my personal data for the purpose of organizing and conducting the “Postphotographic Images Conference” and for related communication.  
								The data will only be accessible to the staff members responsible for managing this event and will be deleted after the administrative process has been completed, at the latest within one month after the event.
								Further information on the processing of your data can be found in the <a href="https://www.hsbi.de/datenschutzerklaerung" target="_blank">Privacy Policy of Bielefeld University of Applied Sciences and Arts (HSBI)</a>
							</p>
							<div class="checkbox-label-row">
								<input id="confirmCheckbox" type="checkbox" required />
								<label for="confirmCheckbox">I agree that my personal data may be processed for the purpose of organizing and communicating about the “Postphotographic Images Conference”, in accordance with the HSBI Privacy Policy.</label>
							</div>
						</div>
					</div>
				</div>


				<!-- Canvas area with flush buttons below -->
				<div class="board-wrap">
					<div class="canvas-area">
						<div id="board" class="board" aria-label="Drawing surface" role="application"></div>
						<canvas id="previewCanvasInline" width="800" height="800"></canvas>
					</div>

					<div class="actions step1-only">
						<div class="actions-left">
							<button id="undo" class="btn" title="Undo (Ctrl/Cmd+Z)">Undo</button>
							<button id="redo" class="btn" title="Redo (Ctrl/Cmd+Y)">Redo</button>
							<button id="clear" class="btn" title="Clear canvas">Clear</button>
							<button id="randomize" class="btn"
								title="Random design (grid, colors, pattern)">Random</button>
						</div>
						<div class="actions-right">
							<button id="finalize" class="btn primary special-button" title="Show ticket preview">Show
								ticket</button>
						</div>
					</div>

					<div class="actions step2-only">
						<div class="actions-left">
							<button id="backToDraw" class="btn">Draw again</button>
						</div>
						<div class="actions-right">
							<button id="proceed" class="btn primary special-button">Proceed</button>
						</div>
					</div>

					<!-- Hidden fields for Step 2 -->
					<div class="hidden-fields step2-only">
						<input type="hidden" id="hiddenName" />
						<input type="hidden" id="hiddenEmail" />
						<input type="hidden" id="hiddenOrg" />
					</div>

					<div class="actions step3-only">
						<div class="actions-left">
							<button id="backToPreview" class="btn">Back to preview</button>
						</div>
						<div class="actions-right">
							<button id="submitTicket" class="btn primary special-button">Get ticket now</button>
						</div>
					</div>

					<div class="actions step4-only">
						<div class="actions-center">
							<p class="success-message"><?php 
								global $wpdb;
								$settings_table = $wpdb->prefix . 'hsbi_settings';
								$registration_complete_text = $wpdb->get_var($wpdb->prepare(
									"SELECT setting_value FROM {$settings_table} WHERE setting_key = %s",
									'registration_complete_text'
								));
							if (!$registration_complete_text) {
								$registration_complete_text = "Thank you for registering for the Postphotographic Images Conference at HSBI!\nYour registration has been successfully completed.\nYou will receive your personal ticket and further information by email shortly.";
							}
								echo nl2br(esc_html($registration_complete_text));
							?></p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</div>