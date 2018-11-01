		<div id="right">
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar("right_sidebar") ) : ?>
					<ul id="top">
						<li><a href="#">Contact dbc</a></li>
					  <li><a href="#">Arrange a Demonstration</a></li>
					</ul> 
					<div class="right_block">
						<div class="top_right_block">
							<h3>Related Items</h3>
						</div>
						<div class="right_block_bg">
							<ul>
								<li><a href="#">Technical Overview</a></li>
								<li><a href="#">Software Specifications</a></li>
								<li><a href="#">RIN Compliance</a></li>
							</ul>
						</div>	
						<div class="bottom_right_block"></div>
					</div>
					<div class="right_block">
						<div class="top_right_block">
							<h3>Customer Success</h3>
						</div>
						<div class="right_block_bg">
						<img src="img/customer.jpg" alt="" />
						<p>Quisque risus dui, viverra tempor, luctus sit amet, tempus ut, tellus. Vivamus fermentum, felis non tempus ullamcorper, magna mi</p>
						<a href="#" class="learn">Learn more</a>
						</div>
						<div class="bottom_right_block"></div>
					</div>

			<?php endif; ?>
		</div>