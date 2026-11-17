<?php

namespace App\Domain\Scheduling;

use App\Enums\AcademicPeriodStatus;
use App\Models\AcademicPeriod;
use App\Models\Setting;

class PublishPeriodAction
{
    public function __construct(
        private readonly PeriodValidator $validator = new PeriodValidator,
    ) {}

    public function execute(AcademicPeriod $period): PublishPeriodResult
    {
        $previousStatus = $period->status;
        $report = $this->validator->validate($period);

        if ($previousStatus === AcademicPeriodStatus::Closed) {
            return new PublishPeriodResult(
                published: false,
                message: "El periodo {$period->code} está cerrado y no puede volver a publicarse.",
                report: $report,
                previousStatus: $previousStatus,
                currentStatus: $previousStatus,
            );
        }

        if ($previousStatus === AcademicPeriodStatus::Published) {
            return new PublishPeriodResult(
                published: true,
                message: "El periodo {$period->code} ya se encuentra publicado.",
                report: $report,
                previousStatus: $previousStatus,
                currentStatus: $previousStatus,
            );
        }

        $allowPublishWithWarnings = (bool) Setting::getValue('allow_publish_with_warnings', true);
        $counts = $report->counts();

        if ($report->hasErrors()) {
            return new PublishPeriodResult(
                published: false,
                message: "No se puede publicar el periodo {$period->code}: existen {$counts['errors']} error(es) que deben resolverse primero.",
                report: $report,
                previousStatus: $previousStatus,
                currentStatus: $previousStatus,
            );
        }

        if (! $report->canPublish($allowPublishWithWarnings)) {
            return new PublishPeriodResult(
                published: false,
                message: "No se puede publicar el periodo {$period->code}: existen {$counts['warnings']} advertencia(s) y la configuración actual exige resolverlas antes de publicar.",
                report: $report,
                previousStatus: $previousStatus,
                currentStatus: $previousStatus,
            );
        }

        $period->update(['status' => AcademicPeriodStatus::Published]);

        return new PublishPeriodResult(
            published: true,
            message: "Periodo {$period->code} publicado correctamente.",
            report: $report,
            previousStatus: $previousStatus,
            currentStatus: AcademicPeriodStatus::Published,
        );
    }
}
