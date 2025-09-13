<?php

namespace Jk\Vts\Forms;

use Illuminate\Support\Collection;
use Jk\Vts\Services\AimClipList\AimClipListEmailManager;
use Jk\Vts\Services\AimClipList\AimClipListRegistrationEmail;
use Jk\Vts\Services\AimClipList\AimClipListUserMeta;
use Jk\Vts\Services\AimClipList\ClipListMeta;
use Jk\Vts\Services\Logging\LoggerTrait;

class ClipListSignUp {
    use LoggerTrait;

    const successPage = 'aim-100-days-registered';
    private AimClipListUserMeta $userMeta;
    private AimClipListRegistrationEmail $email;
    private ClipListMeta $meta;
    public function __construct(public string $path, public string $url) {
        $this->meta = new ClipListMeta();
        $this->userMeta = new AimClipListUserMeta();
        $this->email = new AimClipListRegistrationEmail($this->path, $this->url);
    }

    /**
     * @param Forminator_Form_Entry_Model $entry      - the entry model.
     * @param int                         $quiz_id    - the quiz id.
     * @param array                       $field_data - the entry data.
     **/
    public function handleQuizSubmission($entry, $quiz_id, $field_data) {
        $user = wp_get_current_user();
        if (!$user) {
            return;
        }
        // so we know we can redirect. 
        // TODO: make this equal the term id of the cliplist they registered for
        // this is a hack, but it works.
        global $clipListRegistrationId;


        $values = Collection::make($field_data)->map(fn($field) => $field['value'])->reject(fn($value) => !is_numeric($value) || $value > 5);
        $this->log()->info("values: " . print_r($values->implode(value:fn($v)=>$v,glue: ', '), true));
        $score = $values->sum();

        if($score>20){
            $this->log()->error("$score: Score too high");
            return;
        }

        $highestScore = $values->count() * 3 + 1;

        $category = match (true) {
            $highestScore * .66 <= $score => "advanced",
            $highestScore * .33 <= $score => "intermediate",
            default => "beginner",
        };

        $this->log()->info("score: $score");
        $this->log()->info("highestScore: $highestScore");
        $this->log()->info("category: $category");
        // register the user for the cliplist that goes with their category.
        $listId = $this->getListIdFromCategory($category, $entry->form_id);
        if(!$listId){
            return;
        }

        if($this->userMeta->hasSubscribedList($user->ID, $listId)){
            $this->log()->info("User already subscribed to list");
            return;
        }
        $this->userMeta->addSubscribedList($user->ID, $listId);

        // send the user a success email.
        $this->email->scheduleRegistrationEmail($listId, $user->ID);

        $clipListRegistrationId = $entry->entry_id;
    }

    // function redirectToSuccessPage(){
    //     global $didSubmit;
    //     if(isset($didSubmit)){
    //         wp_redirect(site_url() . self::successPage);
    //     }
    // }
    private function getListIdFromCategory(string $category, int $id) {
        $lists = get_posts([
            'post_type' => 'aim-clip-list',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => $this->meta::formId,
                    'value' => $id,
                ],
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'aim-clip-list-category',
                    'field' => 'slug',
                    'terms' => $category,
                ],
            ],
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if (count($lists) > 0) {
            return $lists[0]->ID;
        }
        return null;
    }
}
