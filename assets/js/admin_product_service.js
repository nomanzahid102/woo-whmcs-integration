(function ($) {
	function setButtonAbility() {
		var $chk_products = $('input[name="products[]"]:checked');
		if ($chk_products.length > 0) {
			$('.btn-delete-selected').removeAttr("disabled");
		} else {
			$('.btn-delete-selected').attr("disabled", true);
		}
	}

	$(document).ready(function () {
		$('input[name="products[]"]').change(function () {
			setButtonAbility();
		});

		$('#cb-select-all-1').change(function () {
			setButtonAbility();
		});

		function deleteRow(ids){
			//console.log('delete');
			var url = woo_whmcs_app.ajaxUrl;
			var data = {
				'action': 'woo_whmcs_delete_selected_product_service_mapping_action',
				'product_ids': ids
			};

			$.ajax({
				url: url,
				type: "POST",
				data: data,
				dataType: 'json',
				timeout: 0,
				success: function (result) {
					var url = woo_whmcs_app.settingPageUrl;
					window.location.href = url;
					/* var message = `Current import: ${result.imported}, update: ${result.updated}, total: ${result.total}`;
					alert(message); */
					//console.log(result)
					//window.location.reload();
					//console.log(result);
				},

				error: function (xhr) {
					/* $('#import-status').show();
					$('#import-status-text').html('Error when import product'); */
					var err = JSON.parse(xhr.responseText);
					console.log(err);
				},

				/* complete: function () {
					$('.btn-delete-selected').removeAttr('disabled');
					$('.btn-delete-selected-text').html('Delete Selected');
					$('input[name="products[]"]').removeAttr('disabled');
					$('#cb-select-all-1').removeAttr('disabled');
				} */
			});
		}
		$('.link-edit-row').click(function (e) {
			e.preventDefault();
			var url = woo_whmcs_app.formSettingPageUrl + '&action=edit&product_id=' + $(this).data('product_id') + '&service_id=' + $(this).data('service_id')
			window.location.href = url;
			//console.log('edit ' + $(this).data('product_id') + ' - ' + $(this).data('service_id'))
		});	
		$('.link-delete-row').click(function (e) {
			e.preventDefault();
			let delete_confirm = confirm("You are sure?");
			if (delete_confirm == true) {
				deleteRow([$(this).data('id')]);
			}
			//alert('delete');
		});

		$('.btn-delete-selected').click(function (e) {
			e.preventDefault()
			
			
			let delete_confirm = confirm("You are sure?");
			if (delete_confirm == true) {
				var $chk_products = $('input[name="products[]"]:checked');
				var product_ids = [];
				$chk_products.each(function () {
					product_ids.push($(this).val())
				});

				deleteRow(product_ids);
			}
			//alert('delete');
		});
		
		
	});
})(jQuery); 