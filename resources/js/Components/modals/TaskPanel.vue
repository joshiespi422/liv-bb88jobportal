<script setup>
import { shortDateTime } from "../../Composables/useDateFormatter";

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  commentMessage: {
    type: String,
    default: "",
  },
  commentError: {
    type: String,
    default: null,
  },
});

const emit = defineEmits([
  "submit-comment",
  "view-accomplishment",
  "update:comment-message",
  "clear-comment-error",
]);

const handleLocalCommentSubmit = () => {
  // Only emit if there is a message to send
  if (props.commentMessage && props.commentMessage.trim()) {
    emit("submit-comment");
  }
};

const handleEnterKey = (event) => {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    handleLocalCommentSubmit();
  }
};

const onTextareaInput = (event) => {
  emit("update:comment-message", event.target.value);
};

const onTextareaChange = () => {
  if (props.commentError) {
    emit("clear-comment-error");
  }
};
</script>

<template>
  <div class="rounded-xl bg-base-200 @sm:p-2 @3xl:p-3">
    <div class="collapse collapse-plus bg-base-100 border border-base-300">
      <input type="radio" name="my-accordion-3" checked="checked" />
      <div class="collapse-title font-semibold">History Updates</div>
      <div class="collapse-content text-sm px-2 @sm:px-4">
        <ul
          class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
          v-if="task.accomplishments && task.accomplishments.length"
        >
          <li
            v-for="accomplishment in task.accomplishments"
            :key="accomplishment.id"
            class="list-row gap-0 hover:bg-base-300 hover:cursor-pointer"
            @click="emit('view-accomplishment', accomplishment.id)"
          >
            <div>
              <div class="font-semibold truncate">
                {{ accomplishment.user_name }}
              </div>
              <div class="text-xs uppercase font-semibold opacity-60 truncate">
                {{ accomplishment.title }}
              </div>
            </div>
          </li>
        </ul>
        <div
          v-else
          role="alert"
          class="alert alert-warning alert-soft font-semibold"
        >
          <span>No accomplishment found</span>
        </div>
      </div>
    </div>
    <div class="collapse collapse-plus bg-base-100 border border-base-300 mt-1">
      <input type="radio" name="my-accordion-3" />
      <div class="collapse-title font-semibold">Comments</div>
      <div class="collapse-content text-sm px-2 @sm:px-4">
        <ul
          class="list bg-base-200 rounded-box shadow-md overflow-y-auto max-h-60 list-scroll"
        >
          <!-- Comments list -->
          <li
            v-for="comment in task.comments"
            :key="comment.id"
            class="list-row gap-0 p-2 pe-0"
          >
            <div class="chat chat-start">
              <div class="chat-image avatar">
                <div class="w-8 @4xl:w-10 rounded-full">
                  <img :src="comment.user_picture" :alt="comment.user_name" />
                </div>
              </div>

              <div class="chat-bubble max-w-full whitespace-pre-wrap">
                {{ comment.message }}
                <div class="text-xs opacity-50">
                  {{ comment.user_name }} -
                  {{ shortDateTime(comment.created_at) }}
                </div>
              </div>
            </div>
          </li>

          <div class="m-3 grid grid-cols-[4fr_1fr]">
            <textarea
              :value="commentMessage"
              @input="onTextareaInput"
              @keydown="handleEnterKey"
              @change="onTextareaChange"
              placeholder="Write a comment..."
              class="textarea min-h-4 w-full textarea-sm"
              :class="{
                'textarea-primary': !commentError,
                'textarea-error': commentError,
              }"
              required
            ></textarea>
            <div class="flex justify-center items-center">
              <button
                @click="handleLocalCommentSubmit"
                :disabled="!commentMessage.trim()"
                class="btn btn-sm @md:btn-md btn-circle btn-primary"
              >
                <i class="pi pi-send text-lg" />
              </button>
            </div>
          </div>
          <p
            v-if="commentError"
            class="text-sm mb-2 px-2 font-semibold text-error ms-3"
          >
            {{ commentError }}
          </p>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.list-scroll::-webkit-scrollbar {
  width: 6px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 3px;
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  background-color: transparent;
}
</style>
