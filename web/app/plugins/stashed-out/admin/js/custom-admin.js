jQuery( document ).ready(
	function() {
		const Helper = function (e, ui) {
			ui.children().children().each(
				function () {
					jQuery( this ).width( jQuery( this ).width() );
				}
			);
			return ui;
		};
		jQuery( '#featured-tag-top-section' ).insertBefore( '.taxonomy-post_tag .tablenav' ).show();
		jQuery( '.core-updates' ).hide();

		jQuery( '.form-table' ).find( '.user_dob' ).attr( 'placeholder', 'DD-MM-YYYY' );

		if (jQuery( '#user_dob' ).length) {
			jQuery( '#user_dob' ).datepicker(
				{
					dateFormat: 'dd-mm-yy',
					changeMonth: true,
					changeYear: true,
					yearRange: '-100:+0'
				}
			);
		}

		let selectedElemetTitle;
		let selectedElementRow;
		if (jQuery('.front_featured_post').length) {

			const $url = window.location.search;
			let $front_page = 'main';
			if ($url.indexOf('running') !== -1) {
				$front_page = 'running';
			} else if ($url.indexOf('web-development') !== -1) {
				$front_page = 'web-development';
			}

			function getOrder() {
				return jQuery('#the-list input[name="front_featured_post[]"]').map(
					function () {
						return jQuery(this).val();
					}
				).get();
			}

			function ajaxUpdateOrderToNthPosition(position, selectedElemetTitle) {
				jQuery.post(
					ajaxurl,
					{
						action: 'update_frontpage_menu_order',
						frontpage: $front_page,
						order: getOrder()
					}
				).done(
					function (data) {
						alert('Article "' + selectedElemetTitle + '" Changed order position at "' + position + '"');
					}
				);
			}

			const parseQueryString = function () {
				const str = window.location.search;
				const objURL = {};
				str.replace(
					new RegExp("([^?=&]+)(=([^&]*))?", "g"),
					function ($0, $1, $2, $3) {
						objURL[$1] = $3;
					}
				);
				return objURL;
			};
			// Example how to use it:
			const params = parseQueryString();

			/**
			 * if parameter is &post=23423 then the column with post id 23423
			 *
			 * will change the order to 10 where ever the position
			 *
			 * it has previously and change the order on data base as well.
			 */

			if (params.post) {
				let position = 10;

				/**
				 * Check if position parameter is being pass or not.
				 */
				if ('position' in params) {
					position = params.position;
				}

				const selectedPostElement = jQuery('#the-list .check-column input[value="' + params.post + '"]');
				const selectedPostElementIndex = jQuery('#the-list input[name="front_featured_post[]"]').index(selectedPostElement);
				const elementTableRows = jQuery('#the-list tr');
				selectedElementRow = selectedPostElement.parent('th').parent('tr');

				selectedElemetTitle = selectedElementRow.find('.post_title>a').text();

				if (selectedElemetTitle) {
					if ((selectedPostElementIndex > position - 1)) {
						selectedElementRow.insertAfter(elementTableRows.eq(position - 2));
						ajaxUpdateOrderToNthPosition(position, selectedElemetTitle);

					} else if ((selectedPostElementIndex < position - 1)) {
						selectedElementRow.insertAfter(elementTableRows.eq(position - 1));
						ajaxUpdateOrderToNthPosition(position, selectedElemetTitle);

					} else {
						// if element index already 9 do nothing :)
						alert('Article "' + selectedElemetTitle + '" already at position ' + position);
					}
				} else {
					jQuery.post(
						ajaxurl,
						{
							action: 'check_if_postid_exsist',
							frontpage: $front_page,
							post: params.post
						}
					).done(
						function (data) {
							let dataRecieved = JSON.parse(data)
							if (dataRecieved.status === 1) {
								const newOrder = getOrder();
								newOrder.splice(parseInt(position) - 1, 0, params.post);
								newOrder.pop();
								jQuery.post(
									ajaxurl,
									{
										action: 'update_frontpage_menu_order',
										frontpage: $front_page,
										order: newOrder
									}
								).done(
									function (data) {
										alert("Added article " + dataRecieved.title + " on the list at position " + position + " and removed the last article on the list.")
										window.location.reload();
									}
								);
							} else {
								alert("Please make sure this Article ID is Correct");
							}

						}
					);
				}
			}
			const featuredPostListTable = jQuery('table.front_featured_post #the-list');
			featuredPostListTable.sortable(
				{
					'items': 'tr',
					'axis': 'y',
					'helper': Helper,
					'update': function (e, ui) {
						featuredPostListTable.sortable("option", "disabled", true);
						jQuery.post(
							ajaxurl,
							{
								action: 'update_frontpage_menu_order',
								frontpage: $front_page,
								order: getOrder()
							}
						).done(
							function (data) {
								featuredPostListTable.sortable("option", "disabled", false);
							}
						)
					}
				}
			);

			if (jQuery("#grid-position").length) {
				updateTableGridPosition();
				jQuery(".ui-sortable").on(
					"sortupdate",
					function (event, ui) {
						updateTableGridPosition();
						getOrder();
					}
				);
			}

			function updateTableGridPosition() {
				let i = 0;
				jQuery('#the-list > tr').each(
					function (row) {
						let gridPosition = '';
						/* #todo Refactor this */
						if (i === 1 || i === 2) {
							gridPosition += '1R';
						} else if (i == 3) {
							gridPosition += '2R';
						} else if (i == 4 || i == 5) {
							gridPosition += '3R';
						} else if (i == 6 || i == 7 || i == 8) {
							gridPosition += '4R';
						} else if (i == 9 || i == 10) {
							gridPosition += '5R';
						} else if (i == 11) {
							gridPosition += '6R';
						} else if (i == 12 || i == 13) {
							gridPosition += '7R';
						} else if (i == 14 || i == 15 || i == 16) {
							gridPosition += '8R';
						} else if (i == 17) {
							gridPosition += '9R';
						} else if (i == 18 || i == 19) {
							gridPosition += '10R';
						} else if (i == 20 || i == 21 || i == 22) {
							gridPosition += '11R';
						} else if (i == 23 || i == 24) {
							gridPosition += '12R';
						} else if (i == 25) {
							gridPosition += '13R';
						} else if (i == 26 || i == 27) {
							gridPosition += '14R';
						} else if (i == 28 || i == 29 || i == 30) {
							gridPosition += '15R';
						} else if (i == 31) {
							gridPosition += '16R';
						} else if (i == 32 || i == 33) {
							gridPosition += '17R';
						} else if (i == 34 || i == 35 || i == 36) {
							gridPosition += '18R';
						} else if (i == 37) {
							gridPosition += '19R';
						} else if (i == 38 || i == 39) {
							gridPosition += '19R';
						} else if (i == 40 || i == 41 || i == 42) {
							gridPosition += '20R';
						} else if (i == 43) {
							gridPosition += '21R';
						} else if (i == 44 || i == 45) {
							gridPosition += '22R';
						} else if (i == 46 || i == 47 || i == 48) {
							gridPosition += '23R';
						} else if (i == 49) {
							gridPosition += '24R';
						} else if (i == 51 || i == 52) {
							gridPosition += '25R';
						}

						if (i == 0) {
							gridPosition += 'Top Story';
						} else if (i == 3 || i == 11 || i == 19 || i == 27 || i == 35 || i == 43) {
							gridPosition += ' - Triple Tile';
						} else if (i == 4 || i == 12 || i == 20 || i == 28 || i == 36 || i == 44) {
							gridPosition += ' - Double Tile';
						} else {
							gridPosition += ' - Single Tile';
						}

						/**
						 * @NOTE: This is for the scroll depth position.
						 */
						let dmScrollDepth = [22, 23, 24];
						if (dmScrollDepth.indexOf(i) != '-1') {
							gridPosition = "<strong>50% Scroll</strong>" + gridPosition;
						}

						jQuery(this).find('td.grid-position').html(gridPosition);
						i++;
					}
				);
			}
		}

		if (jQuery( '.post-type-article #wpbody-content .featured-post-error' ).length) {
			jQuery( '.notice-success' ).css( 'display', 'none' );
		}
		if (jQuery( '.post-type-opinion-piece #wpbody-content .featured-post-error' ).length) {
			jQuery( '.notice-success' ).css( 'display', 'none' );
		}
		if (jQuery( '.post-type-cartoon #wpbody-content .featured-post-error' ).length) {
			jQuery( '.notice-success' ).css( 'display', 'none' );
		}

		jQuery( '.content_type' ).click(
			function() {
				jQuery.ajax(
					{
						url: ajaxurl,
						type: 'GET',
						dataType: 'html',
						data: {
							'action': 'ajaxArticleContentUpdate',
							'contentType': jQuery( this ).data( 'content-type' ),
							'postId': jQuery( this ).data( 'post-id' ),
							'contentValue': jQuery( this ).is( ':checked' )
						},
						success: function(data) {
							if (jQuery( this ).data( 'tag-filter' ) == 'feature') {
								window.location.href = tagFilterRedirectUrl;
							}
						}
					}
				);
			}
		);

		jQuery( '#delete-link' ).on(
			'click',
			'.eti_remove_image_button',
			function(e) {
				e.preventDefault();
				jQuery( '.ajax-loading-image' ).show();
				jQuery.ajax(
					{
						url: ajaxurl,
						type: 'GET',
						dataType: 'html',
						data: {
							'action': 'ajaxRemoveImageFromTaxonomy',
							'taxonomyId': jQuery( '.eti_remove_image_button' ).data( 'taxonomy-id' )
						},
						success: function(data) {
							jQuery( '.taxonomy-image' ).remove();
							jQuery( '#tag-image' ).removeAttr( 'value' );
							jQuery( '.ajax-loading-image' ).hide();
						}
					}
				);
			}
		);

		// hide the default template on articles.
		if (jQuery( '#page_template' ).length) {
			if (jQuery( '#post_type' ).val() == 'article') {
				const pageTemplate$ = jQuery('#page_template');
				pageTemplate$.children( 'option[value="default"]' ).attr( 'disabled','disabled' ).remove();
				if ( ! jQuery( '#page_template option:selected' ).length) {
					pageTemplate$.children( 'option[value="single-stashed.php"]' ).attr( 'selected','selected' );
				}
			}
		}
		if (jQuery( '#post_author_override' ).length) {
			jQuery( '#post_author_override' ).find( ":selected" ).prepend( '[current] ' )
		}

		// Assign At least One Section for Article Post type
		const $sectionScope = jQuery('#section-all > ul');
		if ($sectionScope.length) {
			jQuery( '#publish' ).click(
				function(){
					if ($sectionScope.find( 'input:checked' ).length) {
						return true;
					} else {
						alert( 'Please choose at least one section, before publishing.' );
						return false;
					}
				}
			);
		}

		// enables the sorting of the featured tags (as we not doing it through the plugin)
		const windowHref = window.location.href;
		if (windowHref.indexOf( 'taxonomy=post_tag&post_type=post&is_feature=feature' ) > -1) {
			const featuredTagListTable = jQuery('table.tags #the-list');
			featuredTagListTable.sortable(
				{
					'items': 'tr',
					'axis': 'y',
					'helper': Helper,
					'update': function (e, ui) {
						featuredTagListTable.sortable( "option", "disabled", true );
						jQuery.post(
							ajaxurl,
							{
								action: 'update_featured_tag_order',
								order: jQuery( '#the-list' ).sortable( 'serialize' )
							}
						).done(
							function (data) {
								featuredTagListTable.sortable( "option", "disabled", false );
							}
						);
					}
				}
			);
		}
	}
);
