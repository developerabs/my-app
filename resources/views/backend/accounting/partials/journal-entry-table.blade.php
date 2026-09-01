<div class="card custom-card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Journal Entries</h5>
            <small class="text-muted">
                Total Debit must equal Total Credit.
            </small>
        </div>

        <button
            type="button"
            class="btn btn-sm btn-primary"
            id="btn-add-row">

            <i class="bi bi-plus-lg"></i>
            Add Row

        </button>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-bordered align-middle mb-0" id="journal-entry-table">

                <thead class="table-light">

                <tr>

                    <th width="50">
                        #
                    </th>

                    <th width="280">
                        Ledger Account
                    </th>

                    <th>
                        Description
                    </th>

                    <th width="140">
                        Debit
                    </th>

                    <th width="140">
                        Credit
                    </th>

                    <th width="180">
                        Reference
                    </th>

                    <th width="60">
                        Action
                    </th>

                </tr>

                </thead>

                <tbody>

                <tr class="journal-row">

                    <td class="text-center row-no">
                        1
                    </td>

                    <td>

                        <select
                            name="entries[0][account_id]"
                            class="form-select select-picker account-select">

                            <option value="">
                                Select Account
                            </option>

                            {{-- Controller থেকে Accounts --}}
                            @foreach($accounts as $group => $items)

                                <optgroup label="{{ $group }}">

                                    @foreach($items as $account)

                                        <option value="{{ $account->id }}">

                                            {{ $account->account_code }}
                                            -
                                            {{ $account->account_name }}

                                        </option>

                                    @endforeach

                                </optgroup>

                            @endforeach

                        </select>

                    </td>

                    <td>

                        <input
                            type="text"
                            name="entries[0][description]"
                            class="form-control">

                    </td>

                    <td>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="entries[0][debit]"
                            class="form-control debit text-end">

                    </td>

                    <td>

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="entries[0][credit]"
                            class="form-control credit text-end">

                    </td>

                    <td>

                        <input
                            type="text"
                            name="entries[0][reference_no]"
                            class="form-control">

                    </td>

                    <td class="text-center">

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger remove-row">

                            <i class="bi bi-trash"></i>

                        </button>

                    </td>

                </tr>

                </tbody>

                <tfoot>

                <tr>

                    <th colspan="3" class="text-end">

                        Total

                    </th>

                    <th>

                        <input
                            type="text"
                            id="total-debit"
                            class="form-control text-end"
                            readonly>

                    </th>

                    <th>

                        <input
                            type="text"
                            id="total-credit"
                            class="form-control text-end"
                            readonly>

                    </th>

                    <th colspan="2">

                        <span
                            id="balance-status"
                            class="badge bg-danger">

                            Not Balanced

                        </span>

                    </th>

                </tr>

                </tfoot>

            </table>

        </div>

    </div>

</div>