jQuery(document).ready(function ($) {

   /* SCHEDULE ACTION */
   $('#sbbg_schedule_action').click(function (e) {
      e.preventDefault();

      var sbbg_file_url = $('input#sbbg_shipping_csv').attr('current_file');

      asdata = {
         'action': 'sbbg_schedule_action_ajax',
         'sbbg_schedule_action': 'true',
         'sbbg_file_url': sbbg_file_url
      };

      $.post(ajaxurl, asdata, function (response) {
         window.alert(response);
         location.reload();
      });
   });

   // SAVE/UPDATE SHIPPING COMPANY DATA
   $('a#sbbg_save_companies').on('click', function (e) {
      e.preventDefault();

      var shipping_co_ref_arr = [];
      var shipping_co_name_arr = [];
      var shipping_co_url_arr = [];

      // company ref
      $('.sbbg_shipping_co_ref').each(function () {
         shipping_co_ref_arr.push($(this).text());
      });

      // company name
      $('.sbbg_shipping_co_name').each(function () {
         shipping_co_name_arr.push($(this).text());
      });

      // company tracking url
      $('.sbbg_shipping_co_url').each(function () {
         shipping_co_url_arr.push($(this).text());
      });


      var data = {
         'action': 'sbbg_save_shipping_cos',
         'shipping_co_ref_data': shipping_co_ref_arr,
         'shipping_co_name_data': shipping_co_name_arr,
         'shipping_co_url_data': shipping_co_url_arr,
      };

      $.post(ajaxurl, data, function (response) {
         alert(response);
         location.reload();
         // console.log(response);
      });

   });

   // ADD SHIPPING COMPANY
   $('td.sbbg_add_shipping_co > a').on('click', function (e) {
      e.preventDefault();

      var sbbg_target = $('#sbbg_shipping_co_data_table > tbody');
      var sbbg_append = '<tr>';
      sbbg_append += '<td class="sbbg_shipping_co_ref" contenteditable = "true" title = "click to edit shipping company reference" > company ref</td>';
      sbbg_append += '<td class="sbbg_shipping_co_name" contenteditable="true" title="click to edit shipping company name">company name</td>';
      sbbg_append += '<td class="sbbg_shipping_co_url" contenteditable="true" title="click to edit shipping company tracking url">tracking url</td>';
      sbbg_append += '<td class="sbbg_add_shipping_co"></td>';
      sbbg_append += '<td class="sbbg_remove_shipping_co"></td>';
      sbbg_append += '</tr >';
      $(sbbg_target).append(sbbg_append);

   });

   // REMOVE SHIPPING COMPANY
   $('td.sbbg_remove_shipping_co > a').on('click', function (e) {
      e.preventDefault();
      $("#sbbg_shipping_co_data_table > tbody > tr:last-child").remove();
   });
});