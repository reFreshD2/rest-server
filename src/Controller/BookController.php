<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\BookRepository;
use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * @Route("/api", name="book-api")
 */
class BookController extends AbstractController
{
    /**
     * @Route("/books", name="books", methods={"GET"})
     */
    public function getBooks(BookRepository $bookRepository)
    {
        $books = $bookRepository->findAll();
        $arrForJSON = array();
        foreach ($books as $book) {
            $arrForJSON[] = $book->json();
        }
        return $this->response($arrForJSON);
    }

    /**
     * @Route("/books", name="books_add", methods={"POST"})
     */
    public function addBook(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository)
    {
        try {
            $request = $this->transformJsonBody($request);
            if (!$request || !$request->get('author') || !$request->get('title')) {
                throw new Exception();
            }
            $book = new Book();
            $book->setAuthor($request->get('author'));
            $book->setTitle($request->get('title'));
            $entityManager->persist($book);
            $entityManager->flush();
            $data = [
                'status' => 200,
                'success' => "Book added successfully",
            ];
            return $this->response($data);
        } catch (Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @Route("/books/{id}", name="books_get", methods={"GET"})
     */
    public function getBook(BookRepository $bookRepository, $id)
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            $data = [
                'status' => 404,
                'errors' => "Book not found",
            ];
            return $this->response($data, 404);
        }
        return $this->response($book->json());
    }

    /**
     * @Route("/books/{id}", name="books_put", methods={"PUT"})
     */
    public function updateBook(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, $id)
    {
        try {
            $book = $bookRepository->find($id);
            if (!$book) {
                $data = [
                    'status' => 404,
                    'errors' => "Post not found",
                ];
                return $this->response($data, 404);
            }
            $request = $this->transformJsonBody($request);
            if (!$request || !$request->request->get('author') || !$request->request->get('title')) {
                throw new Exception();
            }
            $book->setAuthor($request->get('author'));
            $book->setTitle($request->get('title'));
            $entityManager->flush();
            $data = [
                'status' => 200,
                'errors' => "Book updated successfully",
            ];
            return $this->response($data);
        } catch (Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }

    }

    /**
     * @Route("/books/{id}", name="books_delete", methods={"DELETE"})
     */
    public function deleteBook(EntityManagerInterface $entityManager, BookRepository $bookRepository, $id)
    {
        $book = $bookRepository->find($id);
        if (!$book) {
            $data = [
                'status' => 404,
                'errors' => "Book not found",
            ];
            return $this->response($data, 404);
        }
        $entityManager->remove($book);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Book deleted successfully",
        ];
        return $this->response($data);
    }

    public function response($data, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $request;
        }
        $request->request->replace($data);
        return $request;
    }
}
