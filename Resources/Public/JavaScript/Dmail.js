require(['TYPO3/CMS/Backend/date-time-picker'], function (DateTimePicker) {

    document.querySelectorAll('.t3js-datetimepicker')?.forEach((element) => {
      DateTimePicker.initialize(element);
    })

});
