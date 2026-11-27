<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Console\Commands;

use Illuminate\Console\Command;
use Tarkontech\SivarCart\Models\StoreRole;
use Tarkontech\SivarCart\Support\Auth;

/**
 * Creates a member of the staff of the store and gives it a role.
 *
 * The staff of the store are the users of the host application, so the command
 * creates the user with the model of the application and gives it a role of
 * the store with the trait of the roles.
 *
 * It is the command that opens the door of the administration panel: without a
 * member of the staff that holds a role, the panel has nothing to show.
 */
class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sivarcart:create-admin
        {--name= : The name of the member of the staff}
        {--email= : The email of the member of the staff}
        {--password= : The password of the member of the staff}
        {--role=owner : The role of the store the member gets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a member of the staff of the store and give it a role of the store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = Auth::newModel();

        if (! method_exists($model, 'assignStoreRole')) {
            $this->components->error(sprintf(
                'The model [%s] of the application does not carry the trait of the roles of the store. Add [%s] to it and try again.',
                $model::class,
                'Tarkontech\SivarCart\Models\Concerns\HasStoreRoles',
            ));

            return self::FAILURE;
        }

        $name = $this->answer($this->option('name'), 'What is the name of the member of the staff?');
        $email = $this->answer($this->option('email'), 'What is the email of the member of the staff?');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->components->error('The email of the member of the staff is not an email.');

            return self::FAILURE;
        }

        if ($model->newQuery()->where('email', $email)->exists()) {
            $this->components->error(sprintf('The email "%s" is already in use.', $email));

            return self::FAILURE;
        }

        $role = $this->role();

        if (! $role instanceof StoreRole) {
            return self::FAILURE;
        }

        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            $password = (string) $this->secret('What is the password of the member of the staff?');
        }

        if (strlen($password) < 8) {
            $this->components->error('The password of the member of the staff needs at least 8 characters.');

            return self::FAILURE;
        }

        $user = $model->newInstance();

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ])->save();

        $user->assignStoreRole($role);

        $this->components->info(sprintf(
            'The member of the staff "%s" was created with the role "%s".',
            $email,
            $role->slug,
        ));

        return self::SUCCESS;
    }

    /**
     * The role of the store the member gets.
     */
    private function role(): ?StoreRole
    {
        $slug = $this->option('role');

        if (! is_string($slug) || trim($slug) === '') {
            $slug = 'owner';
        }

        $slug = trim($slug);

        $role = StoreRole::query()->where('slug', $slug)->first();

        if ($role instanceof StoreRole) {
            return $role;
        }

        $available = StoreRole::query()
            ->orderBy('slug')
            ->pluck('slug')
            ->all();

        $this->components->error(sprintf(
            'There is no role "%s" in the store. The roles that exist are: %s.',
            $slug,
            $available === [] ? 'none' : implode(', ', $available),
        ));

        return null;
    }

    /**
     * The value of an option, or the answer of the person that runs the command.
     */
    private function answer(mixed $option, string $question): string
    {
        if (is_string($option) && trim($option) !== '') {
            return trim($option);
        }

        return trim((string) $this->ask($question));
    }
}
