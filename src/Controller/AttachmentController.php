<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Form\AttachmentFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/attachments')]
class AttachmentController extends AbstractController
{
    public function __construct(private readonly string $uploadsDir)
    {
    }

    #[Route('/', name: 'attachment_index', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $attachments = $em->getRepository(Attachment::class)->findBy([], ['createdAt' => 'DESC']);

        $form = $this->createForm(AttachmentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            // Extension imposée par le type réel du fichier (déjà validé par le formulaire)
            $extension = AttachmentFormType::ALLOWED_TYPES[$uploadedFile->getMimeType()];
            $newName   = bin2hex(random_bytes(16)) . '.' . $extension;
            $original  = mb_substr(basename($uploadedFile->getClientOriginalName()), 0, 255);

            $uploadedFile->move($this->uploadsDir, $newName);

            $attachment = new Attachment();
            $attachment->setFilename($newName);
            $attachment->setOriginalName($original);

            $em->persist($attachment);
            $em->flush();

            $this->addFlash('success', 'Fichier ajouté');

            return $this->redirectToRoute('attachment_index');
        }

        return $this->render('attachment/index.html.twig', [
            'attachments' => $attachments,
            'form'        => $form->createView(),
        ]);
    }

    // Remplace l'ancien lien direct /uploads/... : accès réservé aux utilisateurs connectés
    #[Route('/{id}', name: 'attachment_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Attachment $attachment): Response
    {
        $path = $this->uploadsDir . '/' . basename($attachment->getFilename());
        if (!is_file($path)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = $this->file(
            $path,
            $attachment->getOriginalName() ?: $attachment->getFilename(),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
        // Le navigateur ne doit jamais réinterpréter le type du fichier (ex. HTML/JS déguisé)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route('/delete/{id}', name: 'attachment_delete', methods: ['POST'])]
    public function delete(Attachment $attachment, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $attachment->getId(), $request->request->get('_token'))) {
            $path = $this->uploadsDir . '/' . basename($attachment->getFilename());
            if (is_file($path)) {
                unlink($path);
            }

            $em->remove($attachment);
            $em->flush();

            $this->addFlash('success', 'Fichier supprimé');
        }

        return $this->redirectToRoute('attachment_index');
    }
}
