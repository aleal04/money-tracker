<?php

namespace App\Models\Period;

use App\Models\Space\SpaceModel;
use App\Helpers\PeriodScheduleHelper;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogicalDeleteTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodModel extends Model
{
    use LogicalDeleteTrait;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'Period';

    /**
     * The name of the primary key.
     *
     * @var string
     */
    protected $primaryKey = 'IdPeriod';

    /**
     * The audit column filled by Eloquent when the row is created.
     *
     * @var string
     */
    const CREATED_AT = 'CreatedAt';

    /**
     * The audit column filled by Eloquent when the row is updated.
     *
     * @var string
     */
    const UPDATED_AT = 'UpdatedAt';

    /**
     * The attributes that are mass assignable (StartDate/EndDate stay 'Y-m-d' strings).
     *
     * @var list<string>
     */
    protected $fillable = ['FkSpace', 'StartDate', 'EndDate', 'Closed'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'FkSpace' => 'integer',
        'Closed' => 'boolean',
        'IsDeleted' => 'boolean',
    ];

    /**
     * Lines of this period.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(LineModel::class, 'FkPeriod', 'IdPeriod');
    }

    /**
     * The space this period belongs to.
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(SpaceModel::class, 'FkSpace', 'IdSpace');
    }

    /**
     * The label of the period per its space frequency, e.g. "I Quincena Octubre 2026".
     */
    public function label(): string
    {
        return PeriodScheduleHelper::label($this->space->Frequency, $this->StartDate, $this->EndDate);
    }

    /**
     * Where the period sits among the projected periods of its space that start in its month (for monthly splits).
     *
     * @return array{start:string,end:string,indexInMonth:int,partsInMonth:int} The period dates and month slot.
     */
    public function monthSlot(): array
    {
        $space = $this->space;

        return [
            'start' => $this->StartDate,
            'end' => $this->EndDate,
            'indexInMonth' => PeriodScheduleHelper::indexInMonth($space->Frequency, $space->CustomDays, $this->StartDate),
            'partsInMonth' => PeriodScheduleHelper::periodsStartingInMonth($space->Frequency, $space->CustomDays, $this->StartDate),
        ];
    }

    /**
     * The stable JSON shape of the period. year/month/half are only set for biweekly periods on calendar halves.
     *
     * @return array{id:int,spaceId:int,startDate:string,endDate:string,year:?int,month:?int,half:?int,closed:bool,label:string}
     */
    public function toView(): array
    {
        $half = PeriodScheduleHelper::half($this->space->Frequency, $this->StartDate, $this->EndDate);

        return [
            'id' => $this->IdPeriod,
            'spaceId' => $this->FkSpace,
            'startDate' => $this->StartDate,
            'endDate' => $this->EndDate,
            'year' => $half === null ? null : (int) substr($this->StartDate, 0, 4),
            'month' => $half === null ? null : (int) substr($this->StartDate, 5, 2),
            'half' => $half,
            'closed' => $this->Closed,
            'label' => $this->label(),
        ];
    }
}
