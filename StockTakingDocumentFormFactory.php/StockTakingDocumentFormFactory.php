<?php

namespace App\Forms;

use App\Engine\StockTaking\StockTakingPdfFacade;
use App\Model\DocumentManager;
use App\Model\ReservationItemManager;
use Nette\Application\UI\Form;
use Nette\Security\User;

final class GenerateStockTakingDocumentFormFactory
{

    public function __construct(
        private readonly FormFactory $factory,
        private readonly StockTakingPdfFacade $stockTakingPdfFacade,
        private readonly DocumentManager $documentManager,
        private readonly User $user,
    )
    {
    }

    public function create()
	{
		$form = $this->factory->create();

		$form->addSelect('type', 'Typ', [
            StockTakingPdfFacade::TYPE_BLIND => 'Slepý',
            StockTakingPdfFacade::TYPE_WITH_QUANTITY => 'S počtem kusů'
        ]);

        $form->addRadioList('orderPrimary', 'Řadit podle (primární)',
            [
                ReservationItemManager::COL_ID => 'ID',
                ReservationItemManager::COL_DETAILS_SHELF => 'Místo',
                ReservationItemManager::COL_TITLE => 'Název',
            ])
            ->setDefaultValue(ReservationItemManager::COL_ID);

        $form->addRadioList('orderPrimaryDirection', 'Směr řazení (primární)',
            [
                'ASC' => 'Vzestupně',
                'DESC' => 'Sestupně',
            ])
            ->setDefaultValue('ASC');

        $form->addRadioList('orderSecondary', 'Řadit podle (sekundární)',
            [
                ReservationItemManager::COL_ID => 'ID',
                ReservationItemManager::COL_DETAILS_SHELF => 'Místo',
                ReservationItemManager::COL_TITLE => 'Název',
            ])
            ->setDefaultValue(ReservationItemManager::COL_ID);

        $form->addRadioList('orderSecondaryDirection', 'Směr řazení (sekundární)',
            [
                'ASC' => 'Vzestupně',
                'DESC' => 'Sestupně',
            ])
            ->setDefaultValue('ASC');

		$form->addSubmit('send', 'Generovat');


		$form->onSuccess[] = function (Form $form, $values) {

            $content = $this->stockTakingPdfFacade->generate(
                $values->type,
                $values->orderPrimary,
                $values->orderPrimaryDirection,
                $values->orderSecondary,
                $values->orderSecondaryDirection,
            );
            file_put_contents('log1.pdf', $content);

            $document = $this->documentManager->addEntity([
                DocumentManager::COLUMN_ORDER_ID => DocumentManager::ORDER_ID_STOCK_TAKING,
                DocumentManager::COLUMN_USER_ID => $this->user->getIdentity()->getId(),
                DocumentManager::COLUMN_NAME => "Inventura",
                DocumentManager::COLUMN_TITLE => "Inventura ze dne " . date("d.m.Y H:i"),
                "content" => $content,
                "extension" => "pdf",
            ]);



            // Redirect to document detail
            $form->getPresenter()->redirect(':Front:Document:detail', $document->id);
		};

		return $form;
	}
}
