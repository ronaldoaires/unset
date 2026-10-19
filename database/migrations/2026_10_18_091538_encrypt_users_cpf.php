
<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Contracts\Encryption\DecryptException;

return new class extends Migration
{
    public function up(): void
    {
        // Remove any unique index involving the plain-text CPF.
        $indexes = DB::select('SHOW INDEX FROM `users`');

        $cpfIndexes = collect($indexes)
            ->filter(fn ($index) =>
                $index->Column_name === 'cpf'
                && (int) $index->Non_unique === 0
            )
            ->pluck('Key_name')
            ->unique();

        foreach ($cpfIndexes as $indexName) {
            $safeIndexName = str_replace('`', '``', $indexName);

            DB::statement(
                "ALTER TABLE `users` DROP INDEX `{$safeIndexName}`"
            );
        }

        // Allow encrypted CPF values, which are longer than 11 characters.
        Schema::table('users', function (Blueprint $table) {
            $table->text('cpf')->nullable()->change();
        });

        // Create the hash column only if it does not exist yet.
        if (! Schema::hasColumn('users', 'cpf_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('cpf_hash', 64)
                    ->nullable()
                    ->after('cpf');
            });
        }

        // Encrypt only plain-text values and rebuild their hashes.
        DB::table('users')
            ->select(['id', 'cpf'])
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $cpf = $user->cpf;
                    $alreadyEncrypted = false;

                    try {
                        $plainCpf = Crypt::decryptString($cpf);
                        $alreadyEncrypted = true;
                    } catch (DecryptException $exception) {
                        $plainCpf = $cpf;
                    }

                    $values = [
                        'cpf_hash' => User::hashCpf($plainCpf),
                    ];

                    if (! $alreadyEncrypted) {
                        $values['cpf'] = Crypt::encryptString($plainCpf);
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update($values);
                }
            });

        // Add the unique hash index only if it is missing.
        $indexes = DB::select('SHOW INDEX FROM `users`');

        $hasUniqueHashIndex = collect($indexes)->contains(
            fn ($index) =>
                $index->Column_name === 'cpf_hash'
                && (int) $index->Non_unique === 0
        );

        if (! $hasUniqueHashIndex) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('cpf_hash', 'users_cpf_hash_unique');
            });
        }
    }

    public function down(): void
    {
        // Restore plain-text CPF values without decrypting them twice.
        DB::table('users')
            ->select(['id', 'cpf'])
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    try {
                        $plainCpf = Crypt::decryptString($user->cpf);
                    } catch (DecryptException $exception) {
                        $plainCpf = $user->cpf;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['cpf' => $plainCpf]);
                }
            });

        // Remove unique indexes involving cpf_hash before dropping the column.
        if (Schema::hasColumn('users', 'cpf_hash')) {
            $indexes = DB::select('SHOW INDEX FROM `users`');

            $hashIndexes = collect($indexes)
                ->filter(fn ($index) =>
                    $index->Column_name === 'cpf_hash'
                    && (int) $index->Non_unique === 0
                )
                ->pluck('Key_name')
                ->unique();

            foreach ($hashIndexes as $indexName) {
                $safeIndexName = str_replace('`', '``', $indexName);

                DB::statement(
                    "ALTER TABLE `users` DROP INDEX `{$safeIndexName}`"
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('cpf_hash');
            });
        }

        // Restore the original CPF column and unique constraint.
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->change();
        });

        $indexes = DB::select('SHOW INDEX FROM `users`');

        $hasUniqueCpfIndex = collect($indexes)->contains(
            fn ($index) =>
                $index->Column_name === 'cpf'
                && (int) $index->Non_unique === 0
        );

        if (! $hasUniqueCpfIndex) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('cpf', 'users_cpf_unique');
            });
        }
    }
};
