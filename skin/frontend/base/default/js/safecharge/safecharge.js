(function ($) {
    "use strict";

    window.safechargeForm = {
        init : function () {
            var me = this;
            this.inputSelector = "#safecharge_cc_number";
            this.selectCardId = "safecharge_cc_token";
            this.selectCardSelector = "#" + this.selectCardId;
            this.listItemClass = "safecharge-list-item";
            this.requiredFields = "required-entry";
            this.nonRequiredFields = "non-required-entry";
            this.selectCardChangeEventHandler();

            $(document).on('keyup', this.inputSelector, function(e) {
                me.safechargeDetectCcType(e);
            });

            $(document).on('change', this.selectCardSelector, function() {
                me.selectCardChangeEventHandler();
            });
        },

        getCreditCardType : function (accountNumber) {
            var result = 'unknown';
            accountNumber = accountNumber.replace(/ /g,'');

            if (/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/.test(accountNumber)) {
                result = 'MC';
            }

            else if (/^4[0-9]{6,}$/.test(accountNumber)) {
                result = 'VI';
            }

            else if (/^3[47][0-9]{13}$/.test(accountNumber)) {
                result = 'AE';
            }

            else if (/^6(?:011|5[0-9]{2})[0-9]{3,}$/.test(accountNumber)) {
                result = 'DI';
            }

            else if (/^(?:2131|1800|35[0-9]{3})[0-9]{3,}$/.test(accountNumber)) {
                result = 'JCB';
            }

            else if (/^3(?:0[0-5]|[68][0-9])[0-9]{4,}$/.test(accountNumber)) {
                result = 'DC';
            }

            return result;
        },

        safechargeDetectCcType : function (event) {
            var value = event.target.value,
                type = this.getCreditCardType(value),
                typeSelectEl = document.getElementById('safecharge_cc_type'),
                active = document.getElementsByClassName('safecharge-cc-type-image active');

            for (var i = 0; i < active.length; i++) {
                active[i].className = 'safecharge-cc-type-image';
            }

            if (type !== 'unknown') {
                typeSelectEl.value = type;

                var img = document.getElementById('safecharge-cc-type-img-' + type);
                if (img) {
                    img.classList.add('active');
                }
            } else {
                typeSelectEl.selectedIndex = 0;
            }
        },

        selectCardChangeEventHandler : function () {
            var selectEl = document.getElementById(this.selectCardId),
                items = document.getElementsByClassName(this.listItemClass);

            if (selectEl.value) {
                for (var i = 0; i < items.length; i++) {
                    items[i].classList.remove('active');

                    var fields = $(items[i]).find('.' + this.requiredFields);
                    for (var j = 0; j < fields.length; j++) {
                        fields[j].setAttribute('old-class', fields[j].className);
                        fields[j].className = this.nonRequiredFields;
                    }
                }

                var selectOpt = $('#' + this.selectCardId).find(':selected');

                $('#safecharge_cc_type').val(selectOpt.attr('data-type'));
                $('#safecharge_expiration').val(selectOpt.attr('data-month'));
                $('#safecharge_expiration_yr').val(selectOpt.attr('data-year'));
            } else {
                for (var i = 0; i < items.length; i++) {
                    items[i].classList.add('active');

                    var fields = $(items[i]).find('.' + this.nonRequiredFields);
                    for (var j = 0; j < fields.length; j++) {
                        var oldClass = fields[j].getAttribute('old-class');
                        fields[j].removeAttribute('old-class');
                        fields[j].className = oldClass;
                    }
                }
            }
        }
    };
})(jQuery);