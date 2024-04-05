<?php

namespace App\Controller;

use App\Entity\PurchaseOrder;
use App\Entity\Vendor;
use App\Form\VendorType;
use App\Repository\PurchaseOrderRepository;
use App\Repository\VendorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VendorController extends AbstractController
{
  public function __construct(
    private EntityManagerInterface $em,
    private VendorRepository $vendor_repo,
    private PaginatorInterface $paginator,
    private PurchaseOrderRepository $po_repo,
  ) {}


  #[Route('/vendor_list/', name: 'vendor_list')]
  public function vendor_list(Request $request): Response
  {
    if (isset($request->query->all()['vendor_code']))
    {
      $vendor = $this->vendor_repo->find($request->query->all()['vendor_code']);
    } else {
      $vendor = new Vendor;
    }
    $vendor_form = $this->createForm(VendorType::class, $vendor);
    $vendor_thead = [
      'vendor_code' => 'Vendor Code',
      'vendor_desc' => 'Vendor Desc',
      'vendor_notes' => 'Vendor Notes',
      'vendor_addr' => 'Vendor Addr',
      'vendor_email' => 'Vendor Email',
      'vendor_phone' => 'Vendor Phone',
    ];
    // to autofill form fields, or leave them null.
    $result = $this->vendor_repo->findAll();
    $result = $this->paginator->paginate($result, $request->query->getInt('page', 1), 100);
    $normalized_vendor = [];
    foreach ($result->getItems() as $item) {
      $normalized_vendor[] = [
        'vendor_code' => $item->getVendorCode(),
        'vendor_desc' => $item->getVendorDesc(),
        'vendor_notes' => $item->getVendorNotes(),
        'vendor_addr' => $item->getVendorAddr(),
        'vendor_email' => $item->getVendorEmail(),
        'vendor_phone' => $item->getVendorPhone(),
      ];
    }
    $result->setItems($normalized_vendor);
    return $this->render('vendor/vendor_list.html.twig', [
      'result' => $result,
      'vendor_thead' => $vendor_thead,
      'form' => $vendor_form,
    ]);
  }

    /**
   * Search for vendor using vendor_code.
   * Should be the standard route for querying for vendor
   * 
   * @author Daniel Boling
   */
  #[Route('/vendor/search/', name:'vendor_search')]
  public function vendor_search(Request $request): Response
  {
    $vendor_form = $this->createForm(vendorType::class);
    $vendor_form->handleRequest($request);
    $vendor = $vendor_form->getData();
    if (!$vendor->getvendorCode()) { return $this->redirectToRoute('vendor_list'); }

    return $this->redirectToRoute('vendor_list', [
      'vendor_code' => $vendor->getvendorCode(),
    ]); 
  }


  /**
   * Creates vendoration from list_vendor page
   * 
   * @author Daniel Boling
   */
  #[Route('/vendor/new/', name:'vendor_create')]
  public function vendor_create(Request $request): Response
  {
    $vendor_form = $this->createForm(vendorType::class);
    $vendor_form->handleRequest($request);
    $vendor = $vendor_form->getData();
    $this->em->persist($vendor);
    $this->em->flush();
    $this->addFlash('success', 'vendor Created');

    return $this->redirectToRoute('vendor_list', [
      'vendor_code' => $vendor->getvendorCode(),
    ]);
  }


  /**
   * Modifies vendor details and redirects back to vendor_list
   * 
   * @author Daniel Boling
   */
  #[Route('/vendor/save/', name:'vendor_save')]
  public function vendor_save(Request $request): Response
  {
    $vendor_form = $this->createForm(vendorType::class);
    $vendor_form->handleRequest($request);
    $vendor = $vendor_form->getData();
    if (!$vendor_form->isValid())
    {
      $this->addFlash('error', 'Error: Invalid Submission - vendoration not updated');
      return $this->redirectToRoute('vendor_search', ['vendor_code' => $vendor->getvendorCode()]);
    }
    if ($this->vendor_repo->find($vendor->getvendorCode())) {
      return $this->redirectToRoute('vendor_modify', ['vendor' => $vendor], 307);
    } else {
      return $this->redirectToRoute('vendor_create', ['vendor' => $vendor], 307);
    }
  }


  /**
   * Handle item modification
   * 
   * @author Daniel Boling
   */
  #[Route('/vendor/modify/', name:'vendor_modify')]
  public function vendor_modify(Request $request): Response
  {
    $vendor_form = $this->createForm(vendorType::class);
    $vendor_form->handleRequest($request);
    $vendor = $vendor_form->getData();
    $this->em->merge($vendor);
    $this->em->flush();
    $this->addFlash('success', 'vendor Updated');
    return $this->redirectToRoute('vendor_list', ['vendor_code' => $vendor->getvendorCode()]);
  }


  /**
   * Deletes vendor if no entites are under it and redirects back to show vendor
   * 
   * @author Daniel Boling
   */
  #[Route('/vendor/delete/', name:'vendor_delete')]
  public function vendor_delete(Request $request): Response
  {
    $vendor_form = $this->createForm(vendorType::class);
    $vendor_form->handleRequest($request);
    $vendor = $vendor_form->getData();
    $vendor = $this->vendor_repo->find($vendor->getvendorCode());
    $this->em->remove($vendor);
    $this->em->flush();
    return $this->redirectToRoute('vendor_list');
    $this->addFlash('success', 'vendor removed.');
  }


  /**
   * Fetches vendor of PO for template fragment
   * 
   * @author Daniel Boling
   */
  public function vendor_details(Request $request, ?string $po_num): Response
  {
    if ($po_num != null)
    {
      $po = $this->po_repo->find($po_num);
    } else {
      $po = new PurchaseOrder;
    }
    $vendor = $po->getPoVendor();
    $vendor_details_form = $this->createForm(VendorType::class, $vendor);
    return $this->render('vendor/vendor_form.html.twig', [
      'form' => $vendor_details_form,
    ]);
  }
}
