<?php

namespace App\Http\Controllers;

use App\Models\WhitelistEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhitelistController extends Controller
{
    protected AuditController $auditController;

    public function __construct(AuditController $auditController)
    {
        $this->auditController = $auditController;
    }

    /**
     * INDEX
     */
    public function index(): View
    {
        return view('dashboard.management.telegram.whitelist', [
            'entries' => WhitelistEntry::latest()->paginate(15),
        ]);
    }

    /**
     * STORE
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => [
                'required',
                'string',
                'max:64',
                'unique:whitelist_entries,chat_id',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $entry = WhitelistEntry::create($data);

        $this->auditController->audit(
            $request,
            'whitelist.created',
            'Whitelist Telegram '.$entry->chat_id.' ditambahkan.',
            $entry
        );

        return back()->with(
            'success',
            'Whitelist Telegram berhasil ditambahkan.'
        );
    }

    /**
     * UPDATE
     */
    public function update(
        Request $request,
        WhitelistEntry $entry
    ): RedirectResponse {
        $data = $request->validate([
            'chat_id' => [
                'required',
                'string',
                'max:64',
                'unique:whitelist_entries,chat_id,'.$entry->id,
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $entry->update($data);

        $this->auditController->audit(
            $request,
            'whitelist.updated',
            'Whitelist Telegram '.$entry->chat_id.' diperbarui.',
            $entry
        );

        return back()->with(
            'success',
            'Whitelist Telegram berhasil diperbarui.'
        );
    }

    /**
     * DESTROY
     */
    public function destroy(
        Request $request,
        WhitelistEntry $entry
    ): RedirectResponse {


        $entry->delete();

        $this->auditController->audit(
            $request,
            'whitelist.deleted',
            'Whitelist Telegram '.$entry->chat_id.' dihapus.',
            $entry
        );

        return back()->with(
            'success',
            'Whitelist Telegram berhasil dihapus.'
        );
    }
}
