/**
 * Bloqueia visibilidade "Somente assinantes" quando não há planos ativos (HAS_SUBSCRIPTION_PLANS na página).
 */
(function ($) {
    $(function () {
        var $modal = $('#noPlansModal');
        var $visibility = $('#visibility');
        if (!$modal.length || !$visibility.length) {
            return;
        }

        var hasPlans = window.HAS_SUBSCRIPTION_PLANS === true;

        window.closeNoPlansModal = function () {
            $modal.addClass('hidden');
            $visibility.val('free');
        };

        $visibility.on('change', function () {
            if ($(this).val() === 'subscriber' && !hasPlans) {
                $modal.removeClass('hidden');
            }
        });

        $modal.on('click', function (e) {
            if ($(e.target).hasClass('js-no-plans-modal-backdrop')) {
                window.closeNoPlansModal();
            }
        });

        // Edição: post já era "assinantes" mas todos os planos ficaram inativos
        if ($visibility.val() === 'subscriber' && !hasPlans) {
            $modal.removeClass('hidden');
        }
    });
})(jQuery);
