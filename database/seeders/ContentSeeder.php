<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CaseSummary;
use App\Models\Note;
use App\Models\Question;
use App\Models\TopicLink;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // ── Topics / Categories ──────────────────────────────────────────
        $topicNotes = Category::updateOrCreate(
            ['slug' => 'contract-law-notes'],
            [
                'name'        => 'Contract Law',
                'type'        => 'notes',
                'description' => 'Fundamentals of contract formation, terms, breach and remedies under Malaysian law.',
                'sort_order'  => 1,
            ]
        );

        $topicCaseLaw = Category::updateOrCreate(
            ['slug' => 'tort-law-cases'],
            [
                'name'        => 'Tort Law',
                'type'        => 'case_law',
                'description' => 'Key negligence, nuisance and defamation cases in Malaysian jurisprudence.',
                'sort_order'  => 2,
            ]
        );

        $topicQBank = Category::updateOrCreate(
            ['slug' => 'land-law-questions'],
            [
                'name'        => 'Land Law',
                'type'        => 'question_bank',
                'description' => 'MCQ practice for National Land Code principles and Torrens title system.',
                'sort_order'  => 3,
            ]
        );

        // ── Notes ────────────────────────────────────────────────────────
        Note::updateOrCreate(
            ['title' => 'Elements of a Valid Contract'],
            [
                'description'  => 'Overview of offer, acceptance, consideration, intention to create legal relations and capacity.',
                'category_id'  => $topicNotes->id,
                'file_path'    => 'notes/sample-contract-law.pdf',
                'file_name'    => 'contract-law-elements.pdf',
                'file_type'    => 'application/pdf',
                'file_size'    => 204800,
                'sort_order'   => 1,
                'is_published' => true,
            ]
        );

        Note::updateOrCreate(
            ['title' => 'Discharge of Contract'],
            [
                'description'  => 'Methods of discharge: performance, agreement, frustration and breach.',
                'category_id'  => $topicNotes->id,
                'file_path'    => 'notes/sample-discharge.pdf',
                'file_name'    => 'discharge-of-contract.pdf',
                'file_type'    => 'application/pdf',
                'file_size'    => 153600,
                'sort_order'   => 2,
                'is_published' => true,
            ]
        );

        // ── Case Summaries ───────────────────────────────────────────────
        CaseSummary::updateOrCreate(
            ['citation' => '[1932] AC 562'],
            [
                'title'        => 'Donoghue v Stevenson',
                'summary_text' => 'Landmark case establishing the modern concept of negligence and the "neighbour principle". A manufacturer owes a duty of care to the ultimate consumer of their product.',
                'category_id'  => $topicCaseLaw->id,
                'is_published' => true,
            ]
        );

        CaseSummary::updateOrCreate(
            ['citation' => '[1861] 1 B&S 393'],
            [
                'title'        => 'Carlill v Carbolic Smoke Ball Co',
                'summary_text' => 'A general offer made to the world at large can constitute a valid binding contract when accepted by performance of the requested act.',
                'category_id'  => $topicCaseLaw->id,
                'is_published' => true,
            ]
        );

        // ── Questions ────────────────────────────────────────────────────
        Question::updateOrCreate(
            ['question_text' => 'Under the National Land Code 1965, what type of title is granted for land held under the Torrens system?'],
            [
                'category_id'          => $topicQBank->id,
                'options'              => [
                    'Possessory title',
                    'Indefeasible title',
                    'Equitable title',
                    'Customary title',
                ],
                'correct_option_index' => 1,
                'explanation'          => 'Section 340 NLC confers indefeasible title upon registration, subject to statutory exceptions for fraud and forgery.',
                'sort_order'           => 1,
                'is_published'         => true,
            ]
        );

        Question::updateOrCreate(
            ['question_text' => 'Which element is NOT required for a valid contract under the Contracts Act 1950?'],
            [
                'category_id'          => $topicQBank->id,
                'options'              => [
                    'Free consent',
                    'Lawful consideration',
                    'Written form',
                    'Capacity to contract',
                ],
                'correct_option_index' => 2,
                'explanation'          => 'The Contracts Act 1950 does not require contracts to be in writing unless specified by a particular statute.',
                'sort_order'           => 2,
                'is_published'         => true,
            ]
        );

        // ── Topic Links ──────────────────────────────────────────────────
        TopicLink::updateOrCreate(
            ['category_id' => $topicNotes->id, 'title' => 'Contracts Act 1950 (Full Text)'],
            [
                'url'        => 'https://www.agc.gov.my/agcportal/uploads/files/Publications/LOM/EN/Act%20136.pdf',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );

        TopicLink::updateOrCreate(
            ['category_id' => $topicCaseLaw->id, 'title' => 'Malaysian Judiciary Case Search'],
            [
                'url'        => 'https://www.kehakiman.gov.my',
                'sort_order' => 1,
                'is_active'  => true,
            ]
        );
    }
}
