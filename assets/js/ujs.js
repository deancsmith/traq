
/*
 * Traq
 * Copyright (C) 2009-2014 Traq.io
 * Copyright (C) 2009-2014 Jack Polgar
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

(function() {
  $(document).ready(function() {
    var doc;
    doc = $(document);
    $('[data-confirm]').each(function() {
      var href;
      href = $(this).attr('href');
      return window.traq.popoverConfirm($(this), $(this).attr('data-confirm'), function() {
        return window.location.href = href;
      });
    });
    doc.on('click', '[data-ajax=1]', function(event) {
      $.ajax({
        url: $(this).attr('href'),
        dataType: 'script'
      });
      return event.preventDefault();
    });
    doc.on('click', '[data-ajax-confirm]', function() {
      return window.traq.popoverConfirm($(this), $(this).attr('data-ajax-confirm'), function() {
        $.ajax({
          url: $(this).attr('href'),
          dataType: 'script'
        });
        return event.preventDefault();
      });
    });
    doc.on('focus', '[data-autocomplete]', function() {
      return $(this).autocomplete({
        source: $(this).attr('data-autocomplete')
      });
    });
    doc.on('click', '[data-overlay]', function(event) {
      event.preventDefault();
      return window.traq.overlay($(this));
    });
    return doc.on('focus', 'input.datepicker', function() {
      return $(this).datepicker({
        dateFormat: $(this).attr('data-date-format'),
        changeMonth: true,
        changeYear: true
      });
    });
  });

}).call(this);
