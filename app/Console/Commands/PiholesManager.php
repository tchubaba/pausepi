<?php

namespace App\Console\Commands;

use App\Enums\ManagerOptions;
use App\Models\PiHoleBox;
use App\Repositories\PiHoleBoxRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Log;

use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\warning;

class PiholesManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pausepi:manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected array $managerOptions;

    protected PiHoleBoxRepository $piHoleBoxRepository;

    public function __construct(PiHoleBoxRepository $piHoleBoxRepository)
    {
        parent::__construct();

        $this->piHoleBoxRepository = $piHoleBoxRepository;
        $this->managerOptions      = array_column(ManagerOptions::cases(), 'value');
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        info('Welcome to PausePi Manager');

        do {
            $mainMenuOption = select(
                'What would you like to do?',
                $this->managerOptions
            );

            switch ($mainMenuOption) {
                case ManagerOptions::VIEW->value:
                    $piHoles = PiHoleBox::select(['name', 'hostname', 'api_key', 'description'])->get();

                    if ($piHoles->isEmpty()) {
                        warning('No Pi-holes have been added yet. Please add one.');
                    } else {
                        info('These are the currently configured Pi-holes:');
                        table(['Name', 'Hostname', 'API Token', 'Description'], $piHoles->map(
                            function (PiHoleBox $box): PiHoleBox {
                                $box->api_key = Str::of($box->api_key)->limit(10);
                                return $box;
                            }
                        )->toArray());
                    }

                    break;
                case ManagerOptions::ADD->value:
                    info('Please enter the new Pi-hole\'s information:');
                    $name = text(
                        label: 'Name',
                        placeholder: 'A unique name describing your Pi-hole',
                        required: 'A name is required',
                        validate: fn (string $value) => match (true) {
                            strlen($value) < 3  => 'The name must be at least 3 characters long',
                            strlen($value) > 16 => 'The name must not be greater than 16 characters long',
                            default             => null,
                        },
                    );

                    $hostname = text(
                        label: 'Hostname',
                        placeholder: 'An IP address or a hostname like mydomain.com',
                        required: 'A hostname is required'
                    );

                    $apiKey = text(
                        label: 'API Token',
                        placeholder: 'Can be obtained from Settings > API > Show API Token',
                        required: 'An api token is required',
                        validate: fn (string $value) => match (true) {
                            strlen($value) !== 64 => 'The api key must be 64 characters long',
                            default               => null,
                        },
                    );

                    $description = text(
                        label: 'Description',
                        placeholder: 'A description of your Pi-hole. Optional.',
                        validate: fn (string $value) => match (true) {
                            strlen($value) > 100 => 'The description must not be greater than 100 characters long',
                            default              => null,
                        }
                    );

                    info('New Pi-Hole information:');
                    table(['Name', 'Hostname', 'API Token', 'Description'], [[$name, $hostname, $apiKey, $description]]);

                    $confirmed = confirm(
                        label: 'Does the information look correct?',
                        default: true,
                        yes: 'Yes, add new Pi-hole',
                        no: 'No, do not add',
                    );

                    if ($confirmed) {
                        try {
                            $newPihole = new PiHoleBox([
                                'name'        => $name,
                                'hostname'    => $hostname,
                                'api_key'     => $apiKey,
                                'description' => $description,
                            ]);
                            $newPihole->save();
                            info(sprintf('New Pi-hole %s added successfully', $name));
                        } catch (Exception $e) {
                            error('An error occurred and the new Pi-hole could not be added. Please ensure that the name and hostname are unique. See logs for further details.');
                            Log::error($e->getMessage());
                        }
                    } else {
                        warning('Cancelled.');
                    }
                    break;
                case ManagerOptions::EDIT->value:
                    $piHoles = [];
                    /** @var PiHoleBox $piholeBox */
                    foreach ($this->piHoleBoxRepository->getPiholeBoxes() as $piholeBox) {
                        $piHoles[] = $piholeBox->name;
                    }

                    if (empty($piholeBox)) {
                        warning('No Pi-holes have been added yet. Please add one.');
                    } else {
                        $piHoles[] = 'Cancel';
                        $option    = select(
                            'Which Pi-Hole would you like to edit?',
                            $piHoles
                        );

                        if ($option === 'Cancel') {
                            warning('Cancelled.');
                        } else {
                            $piholeBox = PiHoleBox::where('name', $option)->first();

                            if ($piholeBox === null) {
                                error('An error occurred and the selected Pi-hole box could not be found!');
                            } else {
                                info(sprintf('Please update %s\'s information', $piholeBox->name));

                                $name = text(
                                    label: 'Name',
                                    default: $piholeBox->name,
                                    validate: fn (string $value) => match (true) {
                                        strlen($value) < 3  => 'The name must be at least 3 characters long',
                                        strlen($value) > 16 => 'The name must not be greater than 16 characters long',
                                        default             => null,
                                    },
                                );

                                $hostname = text(
                                    label: 'Hostname',
                                    default: $piholeBox->hostname,
                                    required: 'A hostname is required'
                                );

                                $apiKey = text(
                                    label: 'API Token',
                                    default: $piholeBox->api_key,
                                    required: 'An api token is required',
                                    validate: fn (string $value) => match (true) {
                                        strlen($value) !== 64 => 'The api key must be 64 characters long',
                                        default               => null,
                                    },
                                );

                                $description = text(
                                    label: 'Description',
                                    default: $piholeBox->description,
                                    validate: fn (string $value) => match (true) {
                                        strlen($value) > 100 => 'The description must not be greater than 100 characters long',
                                        default              => null,
                                    }
                                );

                                info('Updated Pi-Hole information:');
                                table(['Name', 'Hostname', 'API Token', 'Description'], [[$name, $hostname, $apiKey, $description]]);

                                $confirmed = confirm(
                                    label: 'Does the information look correct?',
                                    default: true,
                                    yes: 'Yes, update Pi-hole',
                                    no: 'No, cancel updating',
                                );

                                if ($confirmed) {
                                    try {
                                        $piholeBox->name        = $name;
                                        $piholeBox->hostname    = $hostname;
                                        $piholeBox->api_key     = $apiKey;
                                        $piholeBox->description = $description;
                                        $piholeBox->save();
                                        info(sprintf('Pi-hole %s updated successfully', $name));
                                    } catch (Exception $e) {
                                        error('An error occurred and the Pi-hole could not be updated. Please ensure that the name and hostname are unique. See logs for further details.');
                                        Log::error($e->getMessage());
                                    }
                                }
                            }
                        }
                    }
                    break;
                case ManagerOptions::REMOVE->value:
                    $piHoles = [];
                    /** @var PiHoleBox $piholeBox */
                    foreach ($this->piHoleBoxRepository->getPiholeBoxes() as $piholeBox) {
                        $piHoles[] = $piholeBox->name;
                    }

                    if (empty($piHoles)) {
                        warning('No Pi-holes have been added yet. Please add one.');
                    } else {
                        $piHoles[] = 'Cancel';
                        $option    = select(
                            'Which Pi-Hole would you like to remove?',
                            $piHoles
                        );

                        if ($option === 'Cancel') {
                            warning('Cancelled.');
                        } else {
                            $confirmed = confirm(
                                label: sprintf('Remove Pi-hole %s. Are you sure?', $option),
                                default: false,
                                yes: 'Yes, remove pihole',
                                no: 'No, do not remove',
                            );

                            if ($confirmed) {
                                $piHoleBox = PiHoleBox::where('name', $option)->first();
                                if ($piHoleBox === null) {
                                    error(sprintf('Could not delete Pi-hole %s. It was not found in the database', $option));
                                } else {
                                    try {
                                        $piHoleBox->delete();
                                        info(sprintf('Pihole %s removed successfully', $option));
                                    } catch (Exception $e) {
                                        error(sprintf('An error occurred and the Pi-hole could not be removed: %s', $e->getMessage()));
                                    }
                                }
                            } else {
                                warning('Cancelled.');
                            }
                        }
                    }
                    break;
                case ManagerOptions::EXIT->value:
                default:
                    info('Exited PausePi manager');
            }
        } while ($mainMenuOption !== ManagerOptions::EXIT->value);
    }
}
