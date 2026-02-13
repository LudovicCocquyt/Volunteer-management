<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Form\AttachmentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/attachments')]
class AttachmentController extends AbstractController
{
    public function __construct(private string $uploadsDir)
    {
        $this->uploadsDir = $uploadsDir;
    }

    #[Route('/', name: 'attachment_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $attachments = $em->getRepository(Attachment::class)->findAll();

        $form = $this->createForm(AttachmentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile) {
                $newName = uniqid().'.'.$uploadedFile->guessExtension();
                $uploadedFile->move($this->uploadsDir, $newName);

                $attachment = new Attachment();
                $attachment->setFilename($newName);
                $attachment->setOriginalName($uploadedFile->getClientOriginalName());

                $em->persist($attachment);
                $em->flush();

                $this->addFlash('success', 'Fichier ajouté');
                return $this->redirectToRoute('attachment_index');
            }
        }

        return $this->render('attachment/index.html.twig', [
            'attachments' => $attachments,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'attachment_delete', methods: ['POST'])]
    public function delete(Attachment $attachment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$attachment->getId(), $request->request->get('_token'))) {

            // Suppression du fichier physique
            $path = $this->uploadsDir.'/'.$attachment->getFilename();
            if (file_exists($path)) {
                unlink($path);
            }

            // Suppression en base
            $em->remove($attachment);
            $em->flush();

            $this->addFlash('success', 'Fichier supprimé');
        }

        return $this->redirectToRoute('attachment_index');
    }
}
