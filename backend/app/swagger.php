<?php

/**
 * @OA\Info(
 *     title="Abbrevio API",
 *     version="1.0.0",
 *     description="API dokumentacija za Abbrevio aplikaciju za upravljanje skraćenicama"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Development API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */

/**
 * @OA\Get(
 *     path="/abbreviations",
 *     tags={"Abbreviations"},
 *     summary="Get abbreviations",
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful response"
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/register",
 *     tags={"Authentication"},
 *     summary="Register a new user",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", example="john@example.com"),
 *             @OA\Property(property="password", type="string", example="password123"),
 *             @OA\Property(property="department", type="string", example="IT")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Korisnik je uspješno kreiran"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", example="john@example.com")
 *                 ),
 *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/login",
 *     tags={"Authentication"},
 *     summary="Login user",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="email", type="string", example="john@example.com"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Uspješna prijava"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="name", type="string", example="John Doe"),
 *                     @OA\Property(property="email", type="string", example="john@example.com")
 *                 ),
 *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...")
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Get(
 *     path="/abbreviations",
 *     tags={"Abbreviations"},
 *     summary="Get list of abbreviations",
 *
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term",
 *         required=false,
 *
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Parameter(
 *         name="category",
 *         in="query",
 *         description="Category filter",
 *         required=false,
 *
 *         @OA\Schema(type="string")
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *
 *                     @OA\Items(
 *
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="abbreviation", type="string", example="API"),
 *                         @OA\Property(property="meaning", type="string", example="Application Programming Interface"),
 *                         @OA\Property(property="description", type="string", example="A set of protocols and tools..."),
 *                         @OA\Property(property="category", type="string", example="Technology")
 *                     )
 *                 )
 *             )
 *         )
 *     )
 * )
 */

/**
 * @OA\Post(
 *     path="/abbreviations",
 *     tags={"Abbreviations"},
 *     summary="Create new abbreviation",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="abbreviation", type="string", example="API"),
 *             @OA\Property(property="meaning", type="string", example="Application Programming Interface"),
 *             @OA\Property(property="description", type="string", example="A set of protocols and tools..."),
 *             @OA\Property(property="category", type="string", example="Technology")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=201,
 *         description="Abbreviation created successfully"
 *     )
 * )
 */
