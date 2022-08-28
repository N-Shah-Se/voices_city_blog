"use strict";

(function ($)
{

	var reloadGutenberg = false;

	/////////// preloading
	function getSuggestions(manualActivate = false){
		$('[data-wpil-ajax-container]').each(function(k, el){
			var $el = $(el);
			var url = $el.attr('data-wpil-ajax-container-url');
			var count = 0;
			var urlParams = parseURLParams(url);

			// don't load the suggestions automatically if the user has selected manual activation
			if($el.data('wpil-manual-suggestions') == 1 && !manualActivate){
				return
			}

			$el.css({'display': 'block'});
			$('.wpil-get-manual-suggestions-container').css({'display': 'none'});

			if(urlParams.type && 'outbound_suggestions_ajax' === urlParams.type[0]){
				ajaxGetSuggestionsOutbound($el, url, count);
			}else if(urlParams.type && 'inbound_suggestions_page_container' === urlParams.type[0]){
				ajaxGetSuggestionsInbound($el, url, count);
			}
		});
	}

	getSuggestions();

	$(document).on('click', '#wpil-get-manual-suggestions', function(e){e.preventDefault(); getSuggestions(true)});

	function ajaxGetSuggestionsInbound($el, url, count, lastPost = 0, processedPostCount = 0, key = null)
	{
		var urlParams = parseURLParams(url);
		var post_id = (urlParams.post_id) ? urlParams.post_id[0] : null;
		var term_id = (urlParams.term_id) ? urlParams.term_id[0] : null;
		var keywords = (urlParams.keywords) ? urlParams.keywords[0] : '';
		var sameCategory = (urlParams.same_category) ? urlParams.same_category[0] : '';
		var selectedCategory = (urlParams.selected_category) ? urlParams.selected_category[0].split(',') : '';
		var sameTag = (urlParams.same_tag) ? urlParams.same_tag[0] : '';
		var selectedTag = (urlParams.selected_tag) ? urlParams.selected_tag[0].split(',') : '';
		var selectPostTypes = (urlParams.select_post_types) ? urlParams.select_post_types[0] : '';
		var selectedPostTypes = (urlParams.selected_post_types) ? urlParams.selected_post_types[0].split(',') : '';
        var nonce = (urlParams.nonce) ? urlParams.nonce[0]: '';

        if(!nonce){
            return;
        }

        // if there isn't a key set, make one
        if(!key){
            while(true){
                key = Math.round(Math.random() * 1000000000);
                if(key > 999999){break;}
            }
        }

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'get_post_suggestions',
                nonce: nonce,
				count: count,
				post_id: post_id,
                term_id: term_id,
				type: 'inbound_suggestions',
				keywords: keywords,
				same_category: sameCategory,
				selected_category: selectedCategory,
				same_tag: sameTag,
				selected_tag: selectedTag,
				last_post: lastPost,
                completed_processing_count: processedPostCount,
				select_post_types: selectPostTypes,
				selected_post_types: selectedPostTypes,
                key: key,
			},
			success: function(response){
				console.log(response);

                // if there was an error
                if(response.error){
                    // output the error message
                    wpil_swal(response.error.title, response.error.text, 'error');
                    // and exit
                    return;
                }

				count = parseInt(count) + 1;
				var progress = Math.floor(response.completed_processing_count / (response.post_count + 0.1) * 100);
				if (progress > 100) {
					progress = 100;
				}
                $('.progress_count').html(progress + '%');
				if(!response.completed){
					ajaxGetSuggestionsInbound($el, url, count, response.last_post, response.completed_processing_count, key);
				}else{
					return updateSuggestionDisplay(post_id, term_id, nonce, $el, 'inbound_suggestions', sameCategory, key, selectedCategory, sameTag, selectedTag, selectPostTypes, selectedPostTypes);
				}
			},
			error: function(jqXHR, textStatus, errorThrown){
                console.log({jqXHR, textStatus, errorThrown});
            }
		});
	}

	function ajaxGetSuggestionsOutbound($el, url, count, post_count = 0, key = null)
	{
        // if there isn't a key set, make one
        if(!key){
            while(true){
                key = Math.round(Math.random() * 1000000000);
                if(key > 999999){break;}
            }
        }

		var urlParams = parseURLParams(url);
		var post_id = (urlParams.post_id) ? urlParams.post_id[0] : null;
		var term_id = (urlParams.term_id) ? urlParams.term_id[0] : null;
		var sameCategory = (urlParams.same_category) ? urlParams.same_category[0] : '';
		var selectedCategory = (urlParams.selected_category) ? urlParams.selected_category[0].split(',') : '';
		var sameTag = (urlParams.same_tag) ? urlParams.same_tag[0] : '';
		var selectedTag = (urlParams.selected_tag) ? urlParams.selected_tag[0].split(',') : '';
		var selectPostTypes = (urlParams.select_post_types) ? urlParams.select_post_types[0] : '';
		var selectedPostTypes = (urlParams.selected_post_types) ? urlParams.selected_post_types[0].split(',') : '';
        var nonce = (urlParams.nonce) ? urlParams.nonce[0]: '';

        if(!nonce){
            return;
        }

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'get_post_suggestions',
                nonce: nonce,
				count: count,
				post_count: (post_count) ? parseInt(post_count): 0,
				post_id: post_id,
                term_id: term_id,
				same_category: sameCategory,
				selected_category: selectedCategory,
				same_tag: sameTag,
				selected_tag: selectedTag,
				select_post_types: selectPostTypes,
				selected_post_types: selectedPostTypes,
				type: 'outbound_suggestions',
                key: key,
			},
			success: function(response){
				console.log({response, count});

                // if there was an error
                if(response.error){
                    // output the error message
                    wpil_swal(response.error.title, response.error.text, 'error');
                    // and exit
                    return;
                }

                // if there was a notice
                if(response.info){
                    // output the notice message
                    wpil_swal(response.info.title, response.info.text, 'info');
                    // and exit
                    return;
                }

				$el.find('.progress_count').html(response.message);

				if((count * response.batch_size) < response.post_count){
					ajaxGetSuggestionsOutbound($el, url, response.count, response.post_count, key);
				}else if( (sameCategory || sameTag) || (0 == wpil_ajax.site_linking_enabled) ){
					// if we're doing same tag or cat matching, skip the external sites.
					return updateSuggestionDisplay(post_id, term_id, nonce, $el, 'outbound_suggestions', sameCategory, key, selectedCategory, sameTag, selectedTag, selectPostTypes, selectedPostTypes);
				}
			},
            error: function(jqXHR, textStatus, errorThrown){
                console.log({jqXHR, textStatus, errorThrown});
            }
		});
	}

	function updateSuggestionDisplay(postId, termId, nonce, $el, type = 'outbound_suggestions', sameCategory = '', key = null, selectedCategory, sameTag, selectedTag, selectPostTypes, selectedPostTypes){
		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'update_suggestion_display',
                nonce: nonce,
				post_id: postId,
                term_id: termId,
                key: key,
				type: type,
				same_category: sameCategory,
				selected_category: selectedCategory,
				same_tag: sameTag,
				selected_tag: selectedTag,
				select_post_types: selectPostTypes,
				selected_post_types: selectedPostTypes,
			},
			success: function(response){
                // if there was an error
                if(response.error){
                    // output the error message
                    wpil_swal(response.error.title, response.error.text, 'error');
                    // and exit
                    return;
                }

                // update the suggestion report
				$el.html(response);
			}
		});
	}

    /**
     * Helper function that parses urls to get their query vars.
     **/
	function parseURLParams(url) {
		var queryStart = url.indexOf("?") + 1,
			queryEnd   = url.indexOf("#") + 1 || url.length + 1,
			query = url.slice(queryStart, queryEnd - 1),
			pairs = query.replace(/\+/g, " ").split("&"),
			parms = {}, i, n, v, nv;
	
		if (query === url || query === "") return;
	
		for (i = 0; i < pairs.length; i++) {
			nv = pairs[i].split("=", 2);
			n = decodeURIComponent(nv[0]);
			v = decodeURIComponent(nv[1]);
	
			if (!parms.hasOwnProperty(n)) parms[n] = [];
			parms[n].push(nv.length === 2 ? v : null);
		}
		return parms;
	}

	function wpilImplodeEls(sep, els)
	{
		var res = [];
		$(els).each(function(k, el) {
			res.push(el.outerHTML);
		});

		return res.join(sep);
	}

	function wpilImplodeText(sep, els)
	{
		var res = [];
		$(els).each(function(k, el) {
			var $el = $(el);
			res.push($el.text());
		});

		return res.join(sep);
	}

	function wpilPushFix($ex)
	{
		var $div = $("<div/>");
		$div.append($ex);
		return $div.html();
	}

	$(document).on('click', '.sentence a', function (e) {
		e.preventDefault();
	});

	var same_category_loading = false;

	$(document).on('click', '#wpil-regenerate-suggestions', function(){
		if (!same_category_loading) {
			same_category_loading = true;
			var container = $(this).closest('[data-wpil-ajax-container]');
			var url = container.attr('data-wpil-ajax-container-url');
			var urlParams = parseURLParams(url);
			var sameCategory = container.find('#field_same_category').prop('checked');
			var selectedCategories = container.find('select[name="wpil_selected_category"').val();
			var sameTag = container.find('#field_same_tag').prop('checked');
			var selectedTags = container.find('select[name="wpil_selected_tag"').val();
			var category_checked = '';
			var tag_checked = '';
			var post_id = (urlParams.post_id) ? urlParams.post_id[0] : 0;
			var postTypeSelect = container.find('#field_select_post_types').prop('checked');
			var postTypes = container.find('select[name="selected_post_types"').val();

			// remove any active same category settings
			url = url.replace('&same_category=true', '');

			//category
			if (sameCategory) {
				url += "&same_category=true";
				url += "&selected_category=" + selectedCategories.join(',');
				category_checked = 'checked="checked"';
			}

			//tag
			if (sameTag) {
				url += "&same_tag=true";
				url += "&selected_tag=" + selectedTags.join(',');
				tag_checked = 'checked="checked"';
			}

			// selected post types
			if(postTypeSelect && postTypes){
				url += "&select_post_types=true";
				url += "&selected_post_types=" + postTypes.join(',');
			}

			if(urlParams.wpil_no_preload && '1' === urlParams.wpil_no_preload[0]){
				var checkAndButton = '<div style="margin-bottom: 30px;">' +
						'<input style="margin-bottom: -5px;" type="checkbox" name="same_category" id="field_same_category_page" ' + category_checked + '>' +
						'<label for="field_same_category_page">Only Show Link Suggestions in the Same Category as This Post</label> <br>' +
					'</div>' +
					'<button id="inbound_suggestions_button" class="sync_linking_keywords_list button-primary" data-id="' + post_id + '" data-type="inbound_suggestions_page_container" data-page="inbound">Custom links</button>';
				container.html(checkAndButton);
			}else{
				container.html('<div class="progress_panel loader"><div class="progress_count" style="width: 100%"></div></div>');
			}

			if(urlParams.type && 'outbound_suggestions_ajax' === urlParams.type[0]){
				ajaxGetSuggestionsOutbound(container, url, 0);
			}else if(urlParams.type && 'inbound_suggestions_page_container' === urlParams.type[0]){
				ajaxGetSuggestionsInbound(container, url, 0);
			}

			same_category_loading = false;
		}
	});

	$(document).on('change', '#field_same_category, #field_same_tag, #field_select_post_types, select[name="wpil_selected_category"], select[name="wpil_selected_tag"], select[name="selected_post_types"], .wpil-suggestions-can-be-regenerated', function(){
		var inputs = $('.wpil-suggestion-input');
		var changed = false;
		inputs.each(function(index, element){
			var el = $(element);
			var initial = el.data('suggestion-input-initial-value');
			if(el.is("input") && el.attr('type') === 'checkbox' && el.is(":checked") != initial){
				changed = true;
			}else if(el.is("input") && el.attr('type') === 'hidden' && el.val() != initial){
				changed = true;
			}else if(el.is("select") && initial.toString() !== el.val().join(',')){
				changed = true;
			}
		});

		if(changed){
			$('#wpil-regenerate-suggestions').removeClass('disabled').prop('disabled', false);
		}else{
			$('#wpil-regenerate-suggestions').addClass('disabled').prop('disabled', true);
		}
	});

	$(document).on('change', '#field_select_post_types,#field_same_tag,#field_same_category', function(){
		var name = $(this).attr('name');
		if($(this).is(":checked")){
			$('.wpil_styles .' + name + '-aux .select2, .' + name + '-aux').css({'display': 'inline-block'});
		}else{
			$('.wpil_styles .' + name + '-aux .select2, .' + name + '-aux').css({'display': 'none'});
		}
	});


	$(document).on('change', '#field_same_category_page', function(){
		var url = document.URL;
		if ($(this).prop('checked')) {
			url += "&same_category=true";
		} else {
			url = url.replace('/&same_category=true/g', '');
		}

		location.href = url;
	});
	function stristr(haystack, needle, bool)
	{
		// http://jsphp.co/jsphp/fn/view/stristr
		// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +   bugfxied by: Onno Marsman
		// *     example 1: stristr('Kevin van Zonneveld', 'Van');
		// *     returns 1: 'van Zonneveld'
		// *     example 2: stristr('Kevin van Zonneveld', 'VAN', true);
		// *     returns 2: 'Kevin '
		var pos = 0;

		haystack += '';
		pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());

		if (pos == -1) {
			return false;
		} else {
			if (bool) {
				return haystack.substr(0, pos);
			} else {
				return haystack.slice(pos);
			}
		}
	}

	function wpil_handle_errors(resp)
	{
		if (stristr(resp, "520") && stristr(resp, "unknown error") && stristr(resp, "Cloudflare")) {
			wpil_swal('Error', "It seems you are using CloudFlare and CloudFlare is hiding some error message. Please temporary disable CloudFlare, open reporting page again, look if it has any new errors and send it to us", 'error')
				.then(wpil_report_next_step);
			return true;
		}

		if (stristr(resp, "504") && stristr(resp, "gateway")) {
			wpil_swal('Error', "504 error: Gateway timeout - please ask your hosting support about this error", 'error')
				.then(wpil_report_next_step);
			return true;
		}

		return false;
	}

	function wpil_report_next_step()
	{
		location.reload();
	}

    /**
     * Makes the call to reset the report data when the user clicks on the "Reset Data" button.
     **/
    function resetReportData(e){
        e.preventDefault();
        var form = $(this);
        var nonce = form.find('[name="reset_data_nonce"]').val();
       
        if(!nonce || form.attr('disabled')){
            return;
        }
        
        // disable the reset button
        form.attr('disabled', true);
        // add a color change to the button indicate it's disabled
        form.find('button.button-primary').addClass('wpil_button_is_active');
        processReportReset(nonce, 0, true);
    }


    var timeList = [];    
    function processReportReset(nonce = null, loopCount = 0, clearData = false){
        if(!nonce){
            return;
        }

        jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'reset_report_data',
                nonce: nonce,
                loop_count: loopCount,
                clear_data: clearData,
			},
            error: function (jqXHR, textStatus) {
				var resp = jqXHR.responseText;

				if (wpil_handle_errors(resp)) {
					wpil_report_next_step();
					return;
				}

				var wrapper = document.createElement('div');
				$(wrapper).append('<strong>' + textStatus + '</strong><br>');
				$(wrapper).append(jqXHR.responseText);
				wpil_swal({"title": "Error", "content": wrapper, "icon": "error"}).then(wpil_report_next_step());
			},
			success: function(response){
                // if there was an error
                if(response.error){
                    wpil_swal(response.error.title, response.error.text, 'error');
                    return;
                }
                
                // if we've been around a couple times without processing links, there must have been an error
                if(!response.links_to_process_count && response.loop_count > 5){
                    wpil_swal('Data Reset Error', 'Link Whisper has tried a number of times to reset the report data, and it hasn\'t been able to complete the action.', 'error');
                    return;
                }

                // if the data has been successfully reset
                if(response.data_setup_complete){
                    // set the loading screen now that the data setup is complete
                    if(response.loading_screen){
                        $('#wpbody-content').html(response.loading_screen);
                    }
                    // set the time
                    timeList.push(response.time);
                    // and call the data processing function to handle the data
                    processReportData(response.nonce, 0, 0, 0);
                }else{
                    // if we're not done processing links, go around again
                    processReportReset(response.nonce, (response.loop_count + 1), true);
                }
			}
		});
    }

    // listen for clicks on the "Reset Data" button
    $('#wpil_report_reset_data_form').on('submit', resetReportData);

	/**
	 * Keeps track of the loop's progress in a global context so the scan is less susceptible to minor errors like timeouts
	 **/
	var globalScan = {
		'nonce': '', 						// nonce
		'loop': 0, 							// loop count
		'link_posts_to_process_count': 0, 	// posts/cats to process count
		'processed': 0, 					// how many have been processed so far
		'meta_filled': false, 				// if the meta processing is complete
		'links_filled': false,				// if the link processing is complete
		'error_count': 0					// the number of times the scan has errored
	};

    /**
     * Process runner that handles the report data generation process.
     * Loops around until all the site's links are inserted into the LW link table
     **/
    function processReportData(nonce = null, loopCount = 0, linkPostsToProcessCount = 0, linkPostsProcessed = 0, metaFilled = false, linksFilled = false){
        if(!nonce){
            return;
        }

        // initialize the stage clock. // The clock is useful for debugging
        if(loopCount < 1){
            if(timeList.length > 0){
                var lastTime = timeList.pop();
                timeList = [lastTime];
            }else{
                timeList = [];
            }
        }

        jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'process_report_data',
                nonce: nonce,
                loop_count: loopCount,
                link_posts_to_process_count: linkPostsToProcessCount,
                link_posts_processed: linkPostsProcessed,
                meta_filled: metaFilled,
                links_filled: linksFilled
			},
            error: function (jqXHR, textStatus, errorThrown) {
				console.log('There has been an error during the scan!');
				console.log(globalScan);
				globalScan.error_count += 1;

				// if the scan has errored less than 5 times, try it again
				if(globalScan.error_count < 5){
					processReportData(
						globalScan.nonce,
						globalScan.loop,
						globalScan.link_posts_to_process_count,
						globalScan.processed,
						globalScan.meta_filled,
						globalScan.links_filled
					);
				}else{
					var resp = jqXHR.responseText;
					if (wpil_handle_errors(resp)) {
						wpil_report_next_step();
						return;
					}

					var wrapper = document.createElement('div');
					$(wrapper).append('<strong>' + textStatus + '</strong><br>');
					$(wrapper).append(jqXHR.responseText);
					wpil_swal({"title": "Error", "content": wrapper, "icon": "error"}).then(wpil_report_next_step());
				}
			},
			success: function(response){
                console.log(response);

                // if there was an error
                if(response.error){
                    // output the error message
                    wpil_swal(response.error.title, response.error.text, 'error');
                    // and exit
                    return;
                }

                // log the time
                timeList.push(response.time);

				// update the global stats
				globalScan.nonce = response.nonce;
				globalScan.loop = 0;
				globalScan.link_posts_to_process_count = response.link_posts_to_process_count;
				globalScan.processed = response.link_posts_processed;
				globalScan.meta_filled = response.meta_filled;
				globalScan.links_filled = response.links_filled;
				globalScan.error_count = 0;

                // if the meta has been successfully processed
				if(response.processing_complete){
					// if the processing is complete
					// console.log the time if available
					if(timeList > 1){
						console.log('The post processing took: ' + (timeList[(timeList.length - 1)] - timeList[0]) + ' seconds.');
					}

					// update the loading bar one more time
					animateTheReportLoadingBar(response);

					// and show the user a success message!
					wpil_swal('Success!', 'Synchronization has been completed.', 'success').then(wpil_report_next_step);
					return;
				} else if(response.link_processing_complete){
					// if we've finished loading links into the link table
					// show the post processing loading page
					if(response.loading_screen){
						$('#wpbody-content').html(response.loading_screen);
					}

					// console.log the time if available
					if(timeList > 1){
						console.log('The link processing took: ' + (timeList[(timeList.length - 1)] - timeList[0]) + ' seconds.');
					}

					// re-call the function for the final round of processing
					processReportData(  response.nonce,
						0,
						response.link_posts_to_process_count,
						0,
						response.meta_filled,
						response.links_filled);

				} else if(response.meta_filled){
					// show the link processing loading screen
					if(response.loading_screen){
						$('#wpbody-content').html(response.loading_screen);
					}
					// console.log the time if available
					if(timeList > 1){
						console.log('The meta processing took: ' + (timeList[(timeList.length - 1)] - timeList[0]) + ' seconds.');
					}

					// update the loading bar
					animateTheReportLoadingBar(response);

					// and recall the function to begin the link processing (loading the site's links into the link table)
					processReportData(  response.nonce,			// nonce
						0,                                      // loop count
						response.link_posts_to_process_count,   // posts/cats to process count
						0,                                      // how many have been processed so far
						response.meta_filled,                   // if the meta processing is complete
						response.links_filled);                 // if the link processing is complete
				} else{
					// update the loop count
					globalScan.loop = (response.loop_count + 1);
                    // if we're not done processing, go around again
                    processReportData(  response.nonce, 
                                        (response.loop_count + 1), 
                                        response.link_posts_to_process_count, 
                                        response.link_posts_processed,
                                        response.meta_filled,
                                        response.links_filled);
                    
                    // if the meta has been processed
                    if(response.meta_filled){
                        // update the loading bar
                        animateTheReportLoadingBar(response);
                    }
                }
			}
		});
    }

    /**
     * Updates the loading bar length and the displayed completion status.
     * 
     * A possible improvement might be to progressively update the loading bar so its more interesting.
     * As it is now, the bar jumps every 60s, so it might be a bit dull and the user might wonder if it's working.
     **/
    function animateTheReportLoadingBar(response){
        // get the loading display
        var loadingDisplay = $('#wpbody-content .wpil-loading-screen');
        // create some variable to update the display with
        var percentCompleted = Math.floor((response.link_posts_processed/response.link_posts_to_process_count) * 100);
        var displayedStatus = percentCompleted + '%' + ((response.links_filled) ? (', ' + response.link_posts_processed + '/' + response.link_posts_to_process_count) : '') + ' ' + wpil_ajax.completed;
//        var oldPercent = parseInt(loadingDisplay.find('.progress_count').css('width'));

        // update the display with the new info
        loadingDisplay.find('.wpil-loading-status').text(displayedStatus);
        loadingDisplay.find('.progress_count').css({'width': percentCompleted + '%'});
    }

    /**
     * Updates the loading bars for linked sites during the link scan.
     * Increases the length of the loading bars and the text content contained in the bar as the data is downloaded.
	 * 
     **/
    function animateLinkedSiteLoadingBar(site, response){
        // create some variables to update the display with
        var percentCompleted = Math.floor((response.saved/response.total) * 100);
        var displayedStatus = percentCompleted + '%' + ((response.saved) ? (', ' + response.saved + '/' + response.total) : '');

        // update the display with the new info
        site.find('.wpil-loading-status').text(displayedStatus);
        site.find('.progress_count').css({'width': percentCompleted + '%'});
    }

	$(document).on('click', '.wpil-collapsible', function (e) {
		if ($(this).hasClass('wpil-no-action') ||
            $(e.target).hasClass('wpil_word') || 
            $(e.target).hasClass('add-internal-links') ||
            $(e.target).hasClass('add_custom_link_button') ||
            $(e.target).hasClass('add_custom_link') || 
            $(e.target).parents('.add_custom_link').length || 
            $(this).find('.custom-link-wrapper').length > 0 || 
            $(this).find('.wp-editor-wrap').length > 0 ||
			$(e.target).hasClass('wpil-reload-sentence-with-anchor') ||
			$(e.target).hasClass('button-primary') ||
			$(e.target).hasClass('button-secondary')
        ) 
        {
			return;
		}

		// exit if the user clicked the "Add" button in the link report
		if($(e.srcElement).hasClass('add-internal-links')){
			return;
		}
		e.preventDefault();

		var $el = $(this);
		var $content = $el.closest('.wpil-collapsible-wrapper').find('.wpil-content');
		var cl_active = 'wpil-active';
		var wrapper = $el.parents('.wpil-collapsible-wrapper');

		if ($el.hasClass(cl_active)) {
			$el.removeClass(cl_active);
			wrapper.removeClass(cl_active);
			$content.hide();
		} else {
			// if this is the link report or target keyword report or autolink table or the domains table
			if($('.tbl-link-reports').length || $('#wpil_target_keyword_table').length || $('#wpil_keywords_table').length || $('#report_domains').length){
				// hide any open dropdowns in the same row
				$(this).closest('tr').find('td .wpil-collapsible').removeClass('wpil-active');
				$(this).closest('tr').find('td .wpil-collapsible-wrapper').removeClass('wpil-active');
				$(this).closest('tr').find('td .wpil-collapsible-wrapper').find('.wpil-content').hide();
			}
			$el.addClass(cl_active);
			wrapper.addClass(cl_active);
			$content.show();
		}
	});

	$(document).on('click', '#select_all', function () {
		if ($(this).prop('checked')) {
			if ($('.best_keywords').hasClass('outbound')) {
				$(this).closest('table').find('.sentence:visible input[type="checkbox"].chk-keywords:visible').prop('checked', true);
			} else {
				$(this).closest('table').find('input[type="checkbox"].chk-keywords:visible').prop('checked', true);
			}

			$('.suggestion-select-all').prop('checked', true);
		} else {
			$(this).closest('table').find('input[type="checkbox"].chk-keywords').prop('checked', false);
			$('.suggestion-select-all').prop('checked', false);
		}
	});

	$(document).on('click', '.best_keywords.outbound .wpil-collapsible-wrapper input[type="radio"]', function () {
		var id = $(this).data('id');
		var data = $(this).closest('li').find('.data').html();
		var type = $(this).data('type');
		var suggestion = $(this).data('suggestion');
		var origin = $(this).data('post-origin');
		var siteUrl = $(this).data('site-url');

		$(this).closest('ul').find('input').prop('checked', false);

		$(this).prop('checked', true);
		$(this).closest('tr').find('input[type="checkbox"]').prop('checked', false);
		$(this).closest('tr').find('input[type="checkbox"]').val(suggestion + ',' + id);

		if (!$(this).closest('tr').find('input[data-wpil-custom-anchor]').length && $(this).closest('tr').find('.sentence[data-id="'+id+'"][data-type="'+type+'"]').length) {
			$(this).closest('tr').find('.sentences > div').hide();
			$(this).closest('tr').find('.sentence[data-id="'+id+'"][data-type="'+type+'"]').show();
		}
	});

	$(document).ready(function(){
		var saving = false;

		if ($('#inbound_suggestions_page').length) {
			var id  = $('#inbound_suggestions_page').data('id');
			var type  = $('#inbound_suggestions_page').data('type');

			$.post( ajaxurl, {action: 'wpil_is_inbound_links_added', id: id, type: type}, function(data) {
				if (data == 'success') {
					wpil_swal('Success', 'Links have been added successfully', 'success');
				}
			});
		}

		$(document).on('click', '#select_all', function () {
			if ($(this).prop('checked')) {
				$(this).closest('table').find('input[type="checkbox"]').prop('checked', true);
			} else {
				$(this).closest('table').find('input[type="checkbox"]').prop('checked', false);
			}
		});

		$(document).on('click', '.best_keywords .wpil-collapsible-wrapper input[type="radio"]', function(){
			var data = $(this).closest('li').find('.data').html();
			var id = $(this).data('id');
			var type = $(this).data('type');
			var suggestion = $(this).data('suggestion');
			$(this).closest('ul').find('input').prop('checked', false);

			$(this).prop('checked', true);
//			$(this).closest('.wpil-collapsible-wrapper').find('.wpil-collapsible-static').html('<div data-id="' + id + '" data-type="' + type + '">' + data + '<span class="add_custom_link_button"> | <a href="javascript:void(0)">Custom Link</a></span><span class="wpil_add_link_to_ignore"> | <a href="javascript:void(0)">Ignore Link</a></span></div>');
			$(this).closest('tr').find('input[type="checkbox"]').prop('checked', false);
			$(this).closest('tr').find('input[type="checkbox"]').val(suggestion + ',' + id);

			if (!$(this).closest('tr').find('input[data-wpil-custom-anchor]').length && $(this).closest('tr').find('.sentence[data-id="'+id+'"][data-type="'+type+'"]').length) {
				$(this).closest('tr').find('.sentences > div').hide();
				$(this).closest('tr').find('.sentence[data-id="'+id+'"][data-type="'+type+'"]').show();
			}
		});

		$(document).on('click', '.link_copy', function(){
			$(this).blur();
			var row = $(this).closest('tr');
			var link = row.find('.post-slug:first').attr('href');


			copyTextToClipboard(link);

			// if Classic or Gutenberg are visible, show the success panel that allows scrolling to the text
			if( $('#wp-content-wrap:visible').length || (wp.blockEditor && $('.block-editor-block-list__layout.is-root-container:visible').length) ){
				wpil_swal({
					title: 'Success!',
					text: 'Link copied successfully!',
					icon: 'success',
					buttons: ['OK', 'Scroll To Text'],
				}).then((scroll) => {
					if (scroll) {
						focusTextSelection(row);
					}
				});
			}else{
				// if the standard editors aren't available, show the old popup
				wpil_swal({
					title: 'Success!',
					text: 'Link copied successfully!',
					icon: 'success',
				});
			}
		});

		function fallbackCopyTextToClipboard(text) {
			var textArea = document.createElement("textarea");
			textArea.value = text;
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();

			try {
				var successful = document.execCommand('copy');
				var msg = successful ? 'successful' : 'unsuccessful';
				console.log('Fallback: Copying text command was ' + msg);
			} catch (err) {
				console.error('Fallback: Oops, unable to copy', err);
			}

			document.body.removeChild(textArea);
		}

		function copyTextToClipboard(text) {
			if (!navigator.clipboard) {
				fallbackCopyTextToClipboard(text);
				return;
			}
			navigator.clipboard.writeText(text).then(function() {
				console.log('Async: Copying to clipboard was successful!');
			}, function(err) {
				console.error('Async: Could not copy text: ', err);
			});
		}

		function focusTextSelection(row){
			// get the sentence
			var sentence = decodeURIComponent(atob($(row.find('input[name="sentence"]')[0]).val()));

			// get the anchor text
			var anchorText = row.find('.sentence a').filter(':visible').text();

			// deselect any active selections
			$('#wpil-free-highlight').contents().unwrap();

			if($('#wp-content-wrap').length){ // Classic
				var tinyMCEVisible = $("#wp-content-wrap").hasClass("tmce-active");

				if(tinyMCEVisible){
					var element = $("#content_ifr").contents().find('*:contains("' + sentence + '"):last');
					
					// if we have the element that contains the sentence
					if(element.length){
						// obtain the element's inner html
						var elementContent = $(element[0]).html().toString();
						// create a new sentence that focuses on the anchor
						var newSentence = sentence.replace(anchorText, '<wpil-free-highlight id="wpil-free-highlight">' + anchorText + '</wpil-free-highlight>');
						// replace the old sentence with the new one
						elementContent = elementContent.replace(sentence, newSentence);
						// update the element's html with the new tags
						$(element[0]).html(elementContent);
						var newElement = $(element).find('#wpil-free-highlight').get();
						newElement = newElement[0];
						// remove the custom tags to create a text node with no tags
						$(newElement).contents().unwrap();
						// find the new text node
						$(element).contents().each(function(index, node){
							if($(node).text() === anchorText){
								SelectText(node);
								scrollVisualModeToStartElement(window.tinymce.get( 'content' ), element);
								$("#content_ifr").focus();
							}
						});
					}
					
				}else{
					var element = $("#wp-content-editor-container textarea.wp-editor-area");

					if(element.length){
						var start = $(element[0]).text().indexOf(anchorText, $(element[0]).text().indexOf(sentence));
						element[0].setSelectionRange(start, start + anchorText.length);
						element[0].focus();
					}
				}

			}else if(wp.blockEditor && $('.block-editor-block-list__layout.is-root-container').length){ // Gutenberg
				var windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
				var element = $(".block-editor-block-list__layout.is-root-container").contents().find('*:contains("' + sentence + '"):last');
				var found = false;

				// if we couldn't find the block with the first check
				if(element.length < 1){
					// go over the elements a different way to try and find the block
					$(".block-editor-block-list__layout.is-root-container").contents().each(function(index, block){
							var sentenceCheck = $(block).html().toString().indexOf(sentence);
							if(!found && sentenceCheck !== -1){
								element = $(block);
								found = true;
							}
						}
					);
				}
				
				// if we have the element that contains the sentence
				if(element.length){
					// remove any pre-existing highlights
					$('#wpil-free-highlight').contents().unwrap();
					// obtain the element's inner html
					var elementContent = $(element[0]).html().toString();
					// create a new sentence that focuses on the anchor
					var newSentence = sentence.replace(anchorText, '<wpil-free-highlight id="wpil-free-highlight">' + anchorText + '</wpil-free-highlight>');
					// replace the old sentence with the new one
					elementContent = elementContent.replace(sentence, newSentence);
					// update the element's html with the new tags
					$(element[0]).html(elementContent);
					// select the new element
					var newElement = $(element).find('#wpil-free-highlight').get();
					// establish the point that we'll be scrolling to
					var scrollPoint = $('.edit-post-visual-editor').offset().top - $(newElement[0]).offset().top;
					scrollPoint = scrollPoint - 61 + (windowHeight / 2);
					// scroll to the point
					$('.interface-interface-skeleton__content').animate( {
						scrollTop: Math.abs(parseInt(scrollPoint))
					}, 1000 );
				}
			}
		}

		function SelectText(element) {
			var frame = document.getElementById("content_ifr"), 
				win = (frame) ? frame.contentWindow : window,
				doc = (frame) ? frame.contentWindow.document : document,
				range, 
				selection;    
			if (doc.body.createTextRange) {
				range = doc.body.createTextRange();
				range.moveToElementText(element);
				range.select();
			} else if (win.getSelection) {
				selection = win.getSelection();        
				range = doc.createRange();
				range.selectNodeContents(element);
				selection.removeAllRanges();
				selection.addRange(range);
			}
		}

		/**
		 * Scrolls the content to place the selected element in the center of the screen.
		 *
		 * Takes an element, that is usually the selection start element, selected in
		 * `focusHTMLBookmarkInVisualEditor()` and scrolls the screen so the element appears roughly
		 * in the middle of the screen.
		 *
		 * In order to achieve the proper positioning, the editor media bar and toolbar are subtracted
		 * from the window height, to get the proper viewport window, that the user sees.
		 *
		 * @param {Object} editor TinyMCE editor instance.
		 * @param {Object} element HTMLElement that should be scrolled into view.
		 */
		 function scrollVisualModeToStartElement( editor, element ) {
			var elementTop = editor.$( element ).offset().top,
				TinyMCEContentAreaTop = editor.$( editor.getContentAreaContainer() ).offset().top,

				toolbarHeight = getToolbarHeight( editor ),

				edTools = $( '#wp-content-editor-tools' ),
				edToolsHeight = 0,
				edToolsOffsetTop = 0,

				$scrollArea;

			if ( edTools.length ) {
				edToolsHeight = edTools.height();
				edToolsOffsetTop = edTools.offset().top;
			}

			var windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight,

				selectionPosition = TinyMCEContentAreaTop + elementTop,
				visibleAreaHeight = windowHeight - ( edToolsHeight + toolbarHeight );

			// There's no need to scroll if the selection is inside the visible area.
			if ( selectionPosition < visibleAreaHeight ) {
//				return;
			}

			/**
			 * The minimum scroll height should be to the top of the editor, to offer a consistent
			 * experience.
			 *
			 * In order to find the top of the editor, we calculate the offset of `#wp-content-editor-tools` and
			 * subtracting the height. This gives the scroll position where the top of the editor tools aligns with
			 * the top of the viewport (under the Master Bar)
			 */
			var adjustedScroll;
			if ( editor.settings.wp_autoresize_on) {
				$scrollArea = $( 'html,body' );
				adjustedScroll = selectionPosition - visibleAreaHeight / 2; //Math.max( selectionPosition - visibleAreaHeight / 2, edToolsOffsetTop - edToolsHeight );
			} else {
				$scrollArea = $( editor.contentDocument ).find( 'html,body' );
				adjustedScroll = elementTop;
			}

			$scrollArea.animate( {
				scrollTop: parseInt( adjustedScroll, 10 )
			}, 1000 );
		}

		/**
		 * Returns the height of the editor toolbar(s) in px.
		 *
		 * @since 3.9.0
		 *
		 * @param {Object} editor The TinyMCE editor.
		 * @return {number} If the height is between 10 and 200 return the height,
		 * else return 30.
		 */
		 function getToolbarHeight( editor ) {
		 	var $$ = window.tinymce.$;
			var node = $$( '.mce-toolbar-grp', editor.getContainer() )[0],
				height = node && node.clientHeight;

			if ( height && height > 10 && height < 200 ) {
				return parseInt( height, 10 );
			}

			return 30;
		}

		// remove any highlighted text on post save
		$(document).on('click', '.editor-post-publish-button, #wpil-free-highlight', function(){
			$('#wpil-free-highlight').contents().unwrap();
		});

		$(window).on('load', function(){
			if ($('#lw_banner').length) {
				// if the user has clicked on the "close" button on the "Upgrade to Premium" CTA in the Report screen
				$('#lw_banner .close').click(function(){
					// make an ajax call to permanently hide the CTA
					$.ajax({
						type: 'POST',
						url: wpil_ajax.ajax_url,
						data: {
							action: 'dismiss_premium_notice',
						},
						success: function(response){
							console.log(response);
							$('#lw_banner').remove();
						},
					});
				});
			}
		});

		//show links chart in dashboard
		if ($('#wpil_links_chart').length) {
			var internal = $('input[name="internal_links_count"]').val();
			var external = $('input[name="total_links_count"]').val() - $('input[name="internal_links_count"]').val();

			$('#wpil_links_chart').jqChart({
				title: { text: '' },
				legend: {
					title: '',
					font: '15px sans-serif',
					location: 'top',
					border: {visible: false}
				},
				border: { visible: false },
				animation: { duration: 1 },
				shadows: {
					enabled: true
				},
				series: [
					{
						type: 'pie',
						fillStyles: ['#33c7fd', '#7646b0'],
						labels: {
							stringFormat: '%d',
							valueType: 'dataValue',
							font: 'bold 15px sans-serif',
							fillStyle: 'white',
							fontWeight: 'bold'
						},
						explodedRadius: 8,
						explodedSlices: [1],
						data: [['Internal', internal], ['External', external]],
						labelsPosition: 'inside', // inside, outside
						labelsAlign: 'circle', // circle, column
						labelsExtend: 20,
						leaderLineWidth: 1,
						leaderLineStrokeStyle: 'black'
					}
				]
			});
		}
	});

	var mouseExit;
	$(document).on('mouseover', '.wpil_help i, .wpil_help div', function(){
		clearTimeout(mouseExit);
		$('.wpil_help div').hide();
		$(this).parent().children('div').show();
	});

	$(document).on('mouseout', '.wpil_help i, .wpil_help div', function(){
		var element = this;
		mouseExit = setTimeout(function(){
			$(element).parent().children('div').hide();
		}, 250);
		
	});

	$(document).on('click', '.csv_button', function(){
		$(this).addClass('wpil_button_is_active');
		var type = $(this).data('type');
		var data = null;
		if(type === 'error'){ data = $(this).data('codes'); }
		wpil_csv_request(type, 1, data);
	});

	function wpil_csv_request(type, count, data = null) {
		$.post(ajaxurl, {
			count: count,
			type: type,
			action: 'wpil_csv_export',
			export_data: data
		}, function (response) {
			if (response.error) {
				wpil_swal('Error', response.error, 'error');
			} else {
				console.log(response);
				if (response.filename) {
					$('.csv_button').removeClass('wpil_button_is_active');
					var link = document.createElement('a');
					link.href = response.filename;
					link.download = 'links_export.csv';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				} else {
					wpil_csv_request(response.type, ++response.count, data);
				}
			}
		});
	}

	$(document).on('click', '.return_to_report', function(e){
		e.preventDefault();

		// if a link is specified
		if(undefined !== this.href){
			// parse the url
			var params = parseURLParams(this.href);
			// if the url is back to an edit page
			if(	undefined !== typeof params &&
				( (undefined !== params.action && undefined !== params.post && 'edit' === params.action[0]) || params.direct_return)
			){
				if(params.ret_url && params.ret_url[0]){
					var link = atob(params.ret_url[0]);
				}else{
					var link = this.href;
				}

				// redirect back to the page
				location.href = link;
				return;
			}
		}

		$.post(ajaxurl, {
			action: 'wpil_back_to_report',
		}, function(){
			window.close();
		});
	});

    /** Sticky Header **/
	// Makes the thead sticky to the top of the screen when scrolled down far enough
	if($('.wpil_styles .wp-list-table:not(.sticky-ignore)').length){
		var theadTop = $('.wpil_styles .wp-list-table:not(.sticky-ignore)').offset().top;
		var adminBarHeight = parseInt(document.getElementById('wpadminbar').offsetHeight);
		var scrollLine = (theadTop - adminBarHeight);
		var sticky = false;

		// duplicate the footer and insert in the table head
		$('.wpil_styles .wp-list-table:not(.sticky-ignore) tfoot tr').clone().addClass('wpil-sticky-header').css({'display': 'none', 'top': adminBarHeight + 'px'}).appendTo('.wp-list-table thead');

		// resizes the header elements
		function sizeHeaderElements(){
			// adjust for any change in the admin bar
			adminBarHeight = parseInt(document.getElementById('wpadminbar').offsetHeight);
			$('.wpil-sticky-header').css({'top': adminBarHeight + 'px'});

			// adjust the size of the header columns
			var elements = $('.wpil-sticky-header').find('th');
			$('.wpil_styles .wp-list-table:not(.sticky-ignore) thead tr').not('.wpil-sticky-header').find('th').each(function(index, element){
				var width = getComputedStyle(element).width;

				$(elements[index]).attr('style', 'width:' + width + "!important;");
			});
		}
		sizeHeaderElements();

		function resetScrollLinePositions(){
			if($('.wpil_styles .wp-list-table:not(.sticky-ignore)').length < 1){
				return;
			}
			theadTop = $('.wpil_styles .wp-list-table:not(.sticky-ignore)').offset().top;
			adminBarHeight = parseInt(document.getElementById('wpadminbar').offsetHeight);
			scrollLine = (theadTop - adminBarHeight);
		}

		$(window).on('scroll', function(e){
			var scroll = parseInt(document.documentElement.scrollTop);

			// if we've passed the scroll line and the head is not sticky
			if(scroll > scrollLine && !sticky){
				// sticky the header
				$('.wpil-sticky-header').css({'display': 'table-row'});
				sticky = true;
			}else if(scroll < scrollLine && sticky){
				// if we're above the scroll line and the header is sticky, unsticky it
				$('.wpil-sticky-header').css({'display': 'none'});
				sticky = false;
			}
		});

		var wait;
		$(window).on('resize', function(){
			clearTimeout(wait);
			setTimeout(function(){ 
				sizeHeaderElements(); 
				resetScrollLinePositions();
			}, 150);
		});

		setTimeout(function(){ 
			resetScrollLinePositions();
		}, 1500);
	}
    /** /Sticky Header **/

})(jQuery);
