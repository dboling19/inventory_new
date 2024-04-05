<?php

namespace App\Controller;

use App\Entity\PurchaseOrder;
use App\Entity\PurchaseOrderLine;
use App\Form\PurchaseOrderType;
use App\Repository\LocationRepository;
use App\Repository\PurchaseOrderRepository;
use App\Repository\PurchaseOrderLineRepository;
use App\Repository\TermsRepository;
use App\Repository\VendorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PurchaseOrderController extends AbstractController
{
  public function __construct(
    private EntityManagerInterface $em,
    private ValidatorInterface $validator,
    private PurchaseOrderRepository $po_repo,
    private PurchaseOrderLineRepository $po_line_repo,
    private LocationRepository $loc_repo,
    private VendorRepository $vendor_repo,
    private TermsRepository $terms_repo,
    private PaginatorInterface $paginator,
  ) {}

  #[Route('/po/list', name: 'po_list')]
  public function po_list(Request $request): Response
  {
    if (isset($request->query->all()['po_num']))
    {
      $po = $this->po_repo->find($request->query->all()['po_num']);
    } else {
      $po = new PurchaseOrder;
    }
    $po_form = $this->createForm(PurchaseOrderType::class, $po);
    $po_thead = [
      'po_num' => 'PO',
      'po_vendor' => 'Vendor',
      'po_terms' => 'Terms',
      'po_total_cost' => 'PO Total Cost',
      'po_date' => 'PO Date',
    ];
    // to autofill form fields, or leave them null.
    $po_result = $this->po_repo->findAll();
    $po_result = $this->paginator->paginate($po_result, $request->query->getInt('po_page', 1), 100);
    $normalized_pos = [];
    foreach ($po_result->getItems() as $item)
    {
      $normalized_pos[] = [
        'po_num' => $item->getPoNum(),
        'po_vendor' => $item->getPoVendor()->getVendorCode() ?? '',
        'po_terms' => $item->getPoTerms()->getTermsCode() ?? '',
        'po_total_cost' => $item->getPoTotalCost(),
        'po_date' => $item->getPoDate()->format('d-m-Y'),
      ];
    }
    $po_result->setItems($normalized_pos);
    return $this->render('purchase_order/po_list.html.twig', [
      'po_thead' => $po_thead,
      'po_result' => $po_result,
      'form' => $po_form,
    ]);
  }


  /**
   * Mainly a placeholder for the search functionality.
   * 
   * @author Daniel Boling
   */
  #[Route('/po/search/', name:'po_search')]
  public function po_search(Request $request): Response
  {
    $po_form = $this->createForm(PurchaseOrderType::class);
    $po_form->handleRequest($request);
    $po = $po_form->getData();
    if (!$po->getPoNum()) { return $this->redirectToRoute('po_list'); }

    return $this->redirectToRoute('po_list', [
      'po_num' => $po->getPoNum(),
    ]);
  }
  

  /**
   * Handle po form submission.
   * Redirect to creation or modification fuctions
   * 
   * @author Daniel Boling
   */
  #[Route('/po/save/', name:'po_save')]
  public function po_save(Request $request): Response
  {
    $po_form = $this->createForm(PurchaseOrderType::class);
    $po_form->handleRequest($request);
    $po = $po_form->getData();
    if (!$po_form->isValid())
    {
      $this->addFlash('error', 'Error: Invalid Submission - Purchase Order not updated');
      return $this->redirectToRoute('po_search', ['po_num' => $po->getPoNum()]);
    }
    if ($this->po_repo->find($po->getPoNum())) {
      return $this->redirectToRoute('po_modify', ['po' => $po], 307);
    } else {
      return $this->redirectToRoute('po_create', ['po' => $po], 307);
    }
  }


  /**
   * Handle po modification
   * 
   * @author Daniel Boling
   */
  #[Route('/po/modify/', name:'po_modify')]
  public function po_modify(Request $request): Response
  {
    $po_form = $this->createForm(PurchaseOrderType::class);
    $po_form->handleRequest($request);
    $po = $po_form->getData();
    $errors = $this->validator->validate($po);
    if (count($errors) > 0)
    {
      dd($errors);
    }
    $this->em->merge($po);
    $this->em->flush();
    $this->addFlash('success', 'Purchase Order Updated');
    return $this->redirectToRoute('po_list', ['po_num' => $po->getPoNum()]);
  }


  /**
   * Handle po creation
   * 
   * @author Daniel Boling
   */
  #[Route('/po/create/', name:'po_create')]
  public function po_create(Request $request): Response
  {
    $po_form = $this->createForm(PurchaseOrderType::class);
    $po_form->handleRequest($request);
    $po = $po_form->getData();
    $errors = $this->validator->validate($po);
    if (count($errors) > 0)
    {
      dd($errors);
    }
    $this->em->persist($po);
    $this->em->flush();
    $this->addFlash('success', 'Purchase Order Created');
    return $this->redirectToRoute('po_list', ['po_num' => $po->getPoNum()]);
  }


  /**
   * Delete PO only if quantity = 0
   * 
   * @author Daniel Boling
   */
  #[Route('/po/delete/', name:'po_delete')]
  public function po_delete(Request $request)
  {
    $po_form = $this->createForm(PurchaseOrderType::class);
    $po_form->handleRequest($request);
    $po = $po_form->getData();
    $po = $this->po_repo->find($po->getPoNum());

    $this->em->remove($po);
    $this->em->flush();
    $this->addFlash('success', 'Removed Purchse Order Entry');
    return $this->redirectToRoute('po_list');
  }


  /**
   * Fetches selected purchase order lines for template fragment
   * 
   * @author Daniel Boling
   */
  #[Route('/po_lines_list/', name:'po_create')]
  public function po_lines_list(Request $request, ?string $po_num, ?int $po_line_page = 1): Response
  {
    $po_line_thead = [
      'po_line' => 'PO Line',
      'po_status' => 'PO Status',
      'item' => 'Item Code',
      'qty_ordered' => 'Qty Ordered',
      'qty_received' => 'Qty Received',
      'qty_rejected' => 'Qty Rejected',
      'qty_vouchered' => 'Qty Vouchered',
      'item_cost' => 'Item Cost',
      'po_due_date' => 'PO Due Date',
      'po_received_date' => 'PO Recieved Date',
      'po_received' => 'PO Received',
      'po_paid' => 'PO Paid',
      'item_qty' => 'Item Qty',
    ];
    // to autofill form fields, or leave them null.
    $result = $this->po_line_repo->find_po_lines((int) $po_num);
    $result = $this->paginator->paginate($result, $po_line_page, 10, ['pageParameterName' => 'po_line_page']);
    $normalized_po_lines = [];
    foreach ($result->getItems() as $item)
    {
      $normalized_po_lines[] = [
        'po_line' => $item->getPoLine(),
        'po_status' => $item->getPoStatus(),
        'item' => $item->getItem()->getItemCode(),
        'qty_ordered' => $item->getQtyOrdered(),
        'qty_received' => $item->getQtyReceived(),
        'qty_rejected' => $item->getQtyRejected(),
        'qty_vouchered' => $item->getQtyVouchered(),
        'item_cost' => $item->getItemCost(),
        'po_due_date' => date_format($item->getPoDueDate(), 'Y-m-d'),
        'po_received_date' => date_format($item->getPoReceivedDate(), 'Y-m-d'),
        'po_received' => $item->getPoReceived(),
        'po_paid' => $item->getPoPaid(),
        'item_qty' => $item->getItemQuantity(),
      ];
    }
    $result->setItems($normalized_po_lines);
    return $this->render('purchase_order/po_lines.html.twig', [
      'po_lines' => $result,
      'po_line_thead' => $po_line_thead,
    ]);
  }


}
