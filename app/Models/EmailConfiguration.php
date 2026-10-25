<?php

namespace App\Models;

use App\Models\Concerns\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class EmailConfiguration extends Model
{
    use BelongsToInstitution;

    public const ENCRYPTIONS = ['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', '' => 'None'];

    protected $fillable = ['name', 'from_name', 'from_address', 'host', 'port', 'encryption', 'username', 'password', 'is_default', 'user_id'];

    protected $casts = [
        'password'   => 'encrypted',
        'is_default' => 'boolean',
        'port'       => 'integer',
    ];

    protected $hidden = ['password'];

    public static function default(): ?self
    {
        return static::orderByDesc('is_default')->orderBy('id')->first();
    }

    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->where('is_default', true)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /** A Laravel mailer that sends through this account. */
    public function mailer(): \Illuminate\Contracts\Mail\Mailer
    {
        $name = 'email_configuration_' . $this->id;

        config(["mail.mailers.{$name}" => [
            'transport'  => 'smtp',
            'host'       => $this->host,
            'port'       => $this->port,
            'encryption' => $this->encryption ?: null,
            'username'   => $this->username,
            'password'   => $this->password,
            'timeout'    => 30,
        ]]);

        Mail::purge($name); // pick up edits to this account

        return Mail::mailer($name);
    }

    public function label(): string
    {
        return "{$this->name} <{$this->from_address}>";
    }
}
