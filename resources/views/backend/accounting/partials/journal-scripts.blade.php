<script>
"use strict";

$(function () {

    let rowIndex = $('#journal-entry-table tbody tr').length;

    /*
    |--------------------------------------------------------------------------
    | Add Row
    |--------------------------------------------------------------------------
    */

    $('#btn-add-row').on('click', function () {

        let $lastRow = $('#journal-entry-table tbody tr:last');

        let $newRow = $lastRow.clone();

        $newRow.find('input').val('');

        $newRow.find('select').val('').trigger('change');

        $newRow.find('[name]').each(function () {

            let name = $(this).attr('name');

            name = name.replace(/\[\d+\]/, '[' + rowIndex + ']');

            $(this).attr('name', name);

        });

        $('#journal-entry-table tbody').append($newRow);

        rowIndex++;
        refreshRows();

    });

    /*
    |--------------------------------------------------------------------------
    | Remove Row
    |--------------------------------------------------------------------------
    */

    $(document).on('click', '.remove-row', function () {

        if ($('#journal-entry-table tbody tr').length === 1) {

            return;

        }

        $(this).closest('tr').remove();

        refreshRows();

        calculateTotals();

    });

    /*
    |--------------------------------------------------------------------------
    | Debit Input
    |--------------------------------------------------------------------------
    */

    $(document).on('input', '.debit', function () {

        let debit = parseFloat($(this).val()) || 0;

        let credit = $(this).closest('tr').find('.credit');

        if (debit > 0) {

            credit.prop('readonly', true).val('');

        } else {

            credit.prop('readonly', false);

        }

        calculateTotals();

    });

    /*
    |--------------------------------------------------------------------------
    | Credit Input
    |--------------------------------------------------------------------------
    */

    $(document).on('input', '.credit', function () {

        let credit = parseFloat($(this).val()) || 0;

        let debit = $(this).closest('tr').find('.debit');

        if (credit > 0) {

            debit.prop('readonly', true).val('');

        } else {

            debit.prop('readonly', false);

        }

        calculateTotals();

    });

    /*
    |--------------------------------------------------------------------------
    | Calculate Totals
    |--------------------------------------------------------------------------
    */

    function calculateTotals() {

        let totalDebit = 0;

        let totalCredit = 0;

        $('.debit').each(function () {

            totalDebit += parseFloat($(this).val()) || 0;

        });

        $('.credit').each(function () {

            totalCredit += parseFloat($(this).val()) || 0;

        });

        $('#total-debit').val(totalDebit.toFixed(2));

        $('#total-credit').val(totalCredit.toFixed(2));

        if (
            totalDebit > 0 &&
            totalDebit.toFixed(2) === totalCredit.toFixed(2)
        ) {

            $('#balance-status')
                .removeClass('bg-danger')
                .addClass('bg-success')
                .text('Balanced');

            $('#btn-save').prop('disabled', false);

        } else {

            $('#balance-status')
                .removeClass('bg-success')
                .addClass('bg-danger')
                .text('Not Balanced');

            $('#btn-save').prop('disabled', true);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Refresh Row Number & Input Names
    |--------------------------------------------------------------------------
    */

    function refreshRows() {

        $('#journal-entry-table tbody tr').each(function (index) {

            $(this).find('.row-no').text(index + 1);

            $(this).find('[name]').each(function () {

                let name = $(this).attr('name');

                name = name.replace(/\[\d+\]/, '[' + index + ']');

                $(this).attr('name', name);

            });

        });

        rowIndex = $('#journal-entry-table tbody tr').length;

    }

});

</script>