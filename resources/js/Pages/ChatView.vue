<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { shortDateTime } from "../Composables/useDateFormatter";

const props = defineProps({
  auth_permissions: Object,
  group_members: Object,
  initial_messages: Array,
  active_group: String,
  current_user: Object,
  has_more: Boolean,
});

// -- messages & pagination part
const messages = ref([...props.initial_messages]);
const hasMore = ref(props.has_more);
const loadingMore = ref(false);

// -- scroll part
const handleScroll = async () => {
  const el = chatContainerRef.value;
  if (!el || loadingMore.value || !hasMore.value) return;
  if (el.scrollTop <= 50) {
    await loadMoreMessages();
  }
};

const loadMoreMessages = async () => {
  if (loadingMore.value || !hasMore.value || messages.value.length === 0)
    return;
  loadingMore.value = true;

  const firstId = messages.value[0].id;
  const prevScrollHeight = chatContainerRef.value?.scrollHeight ?? 0;

  try {
    const res = await fetch(
      route("chat.loadMore") +
        `?group=${props.active_group}&before_id=${firstId}`,
      { headers: { "X-Requested-With": "XMLHttpRequest" } },
    );
    const data = await res.json();

    messages.value = [...data.messages, ...messages.value];
    hasMore.value = data.has_more;

    // Keep user's scroll position — don't jump to top
    nextTick(() => {
      const el = chatContainerRef.value;
      if (el) el.scrollTop = el.scrollHeight - prevScrollHeight;
    });
  } finally {
    loadingMore.value = false;
  }
};

const currentMembers = computed(() => {
  return props.group_members[props.active_group] || [];
});

// -- textarea part
const message = ref("");
const textareaRef = ref(null);

const chatContainerRef = ref(null);
const scrollToBottom = () => {
  nextTick(() => {
    const el = chatContainerRef.value;
    if (el) el.scrollTop = el.scrollHeight;
  });
};

// Function to handle auto-resize
const adjustHeight = () => {
  const el = textareaRef.value;
  if (!el) return;

  el.style.height = "auto";
  const maxHeight = 120;

  if (el.scrollHeight > maxHeight) {
    el.style.height = `${maxHeight}px`;
    el.style.overflowY = "auto";
  } else {
    el.style.height = `${el.scrollHeight}px`;
    el.style.overflowY = "hidden";
  }
};

const handleKeydown = (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
};

const sendMessage = () => {
  if (!message.value.trim()) return;

  router.post(
    route("chat.store"),
    {
      group: props.active_group,
      message: message.value,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        message.value = "";
        nextTick(() => {
          if (textareaRef.value) {
            textareaRef.value.style.height = "auto";
            textareaRef.value.style.overflowY = "hidden";
          }
        });
        scrollToBottom();
      },
    },
  );
};

const switching = ref(false);
const switchTab = (group) => {
  if (switching.value) return;
  if (group === props.active_group) return;

  switching.value = true;

  router.get(
    route("chat"),
    { group: group },
    {
      preserveScroll: false,
      preserveState: false,
      replace: true,
      onFinish: () => {
        switching.value = false;
      },
    },
  );
};

onMounted(() => {
  scrollToBottom();

  chatContainerRef.value?.addEventListener("scroll", handleScroll);

  window.Echo.private(`chat.${props.active_group}`).listen(
    ".MessageSent",
    (e) => {
      messages.value.push(e);
      scrollToBottom();
    },
  );
});

onUnmounted(() => {
  chatContainerRef.value?.removeEventListener("scroll", handleScroll);
  window.Echo.leave(`chat.${props.active_group}`);
});

const isMe = (msg) => msg.user.id === props.current_user.id;
const avatar = (pic) => pic || "/profile-images/default.png";

watch(
  () => props.initial_messages,
  (newMessages) => {
    messages.value = [...newMessages];
  },
);
</script>

<template>
  <Head title="Chat" />
  <div class="p-2 @lg:p-4 @3xl:p-8">
    <div class="h-[80vh] flex flex-col">
      <div class="flex flex-1 flex-col sm:flex-row gap-5 mx-4 mb-5">
        <div class="tabs tabs-lift w-full h-full [--border:2px]">
          <template v-if="auth_permissions.can_core">
            <input
              type="radio"
              name="chat_tabs"
              class="tab"
              aria-label="Core Group"
              :checked="props.active_group === 'core'"
              @click.prevent="switchTab('core')"
            />
            <div
              class="tab-content bg-base-100 border-green-primary-1 p-6 pe-3"
            >
              <div class="h-[60vh] overflow-hidden">
                <div
                  ref="chatContainerRef"
                  class="h-full overflow-y-auto pe-5 pt-3 flex flex-col list-scroll"
                >
                  <div class="flex-grow" />

                  <template v-for="msg in messages" :key="msg.id">
                    <!-- Other users -->
                    <div v-if="!isMe(msg)" class="chat chat-start mb-1">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(msg.user.picture)"
                            :alt="msg.user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        {{ msg.user.name }}
                        <time class="text-xs opacity-50 ms-1.5">{{
                          shortDateTime(msg.created_at)
                        }}</time>
                      </div>
                      <div class="chat-bubble">{{ msg.message }}</div>
                    </div>

                    <!-- Current user -->
                    <div v-else class="chat chat-end mb-1.5">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(current_user.picture)"
                            :alt="current_user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        You
                        <time class="text-xs opacity-50 ms-1">{{
                          shortDateTime(msg.created_at)
                        }}</time>
                      </div>
                      <div class="chat-bubble">
                        {{ msg.message }}
                      </div>
                    </div>
                  </template>
                </div>
              </div>
              <div class="mt-3">
                <div
                  class="relative flex items-end gap-2 bg-base-100 rounded-2xl border border-base-300 p-2 focus-within:ring-2 focus-within:ring-primary/20 transition-all"
                >
                  <textarea
                    ref="textareaRef"
                    v-model="message"
                    @input="adjustHeight"
                    @keydown="handleKeydown"
                    placeholder="Type a message..."
                    class="textarea textarea-ghost focus:bg-transparent focus:outline-none w-full py-2 px-0 resize-none leading-relaxed overflow-hidden list-scroll"
                    rows="1"
                    style="min-height: 40px; box-sizing: border-box"
                  ></textarea>

                  <button
                    @click="sendMessage"
                    :disabled="!message.trim()"
                    class="btn btn-primary btn-circle btn-sm my-auto"
                  >
                    <i class="pi pi-send text-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template v-if="auth_permissions.can_employees">
            <input
              type="radio"
              name="chat_tabs"
              class="tab"
              aria-label="Employees"
              :checked="props.active_group === 'employees'"
              @click.prevent="switchTab('employees')"
            />
            <div
              class="tab-content bg-base-100 border-green-primary-1 p-6 pe-3"
            >
              <div class="h-[60vh] overflow-y-auto">
                <div
                  class="h-full overflow-y-auto pe-5 pt-3 flex flex-col list-scroll"
                >
                  <div class="flex-grow" />

                  <template v-for="msg in messages" :key="msg.id">
                    <!-- Other users -->
                    <div v-if="!isMe(msg)" class="chat chat-start">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(msg.user.picture)"
                            :alt="msg.user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        {{ msg.user.name }}
                        <time class="text-xs opacity-50 ms-1">{{
                          msg.created_at
                        }}</time>
                      </div>
                      <div class="chat-bubble">{{ msg.message }}</div>
                    </div>

                    <!-- Current user -->
                    <div v-else class="chat chat-end">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(current_user.picture)"
                            :alt="current_user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        You
                        <time class="text-xs opacity-50 ms-1">{{
                          msg.created_at
                        }}</time>
                      </div>
                      <div class="chat-bubble">
                        {{ msg.message }}
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <div class="mt-3">
                <div
                  class="relative flex items-end gap-2 bg-base-100 rounded-2xl border border-base-300 p-2 focus-within:ring-2 focus-within:ring-primary/20 transition-all"
                >
                  <textarea
                    ref="textareaRef"
                    v-model="message"
                    @input="adjustHeight"
                    @keydown="handleKeydown"
                    placeholder="Type a message..."
                    class="textarea textarea-ghost focus:bg-transparent focus:outline-none w-full py-2 px-0 resize-none leading-relaxed overflow-hidden list-scroll"
                    rows="1"
                    style="min-height: 40px; box-sizing: border-box"
                  ></textarea>

                  <button
                    @click="sendMessage"
                    :disabled="!message.trim()"
                    class="btn btn-primary btn-circle btn-sm my-auto"
                  >
                    <i class="pi pi-send text-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </template>

          <template v-if="auth_permissions.can_interns">
            <input
              type="radio"
              name="chat_tabs"
              class="tab"
              aria-label="Interns"
              :checked="props.active_group === 'interns'"
              @click.prevent="switchTab('interns')"
            />
            <div
              class="tab-content bg-base-100 border-green-primary-1 p-6 pe-3"
            >
              <div class="h-[60vh] overflow-y-auto">
                <div
                  class="h-full overflow-y-auto pe-5 pt-3 flex flex-col list-scroll"
                >
                  <div class="flex-grow" />

                  <template v-for="msg in messages" :key="msg.id">
                    <!-- Other users -->
                    <div v-if="!isMe(msg)" class="chat chat-start">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(msg.user.picture)"
                            :alt="msg.user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        {{ msg.user.name }}
                        <time class="text-xs opacity-50 ms-1">{{
                          msg.created_at
                        }}</time>
                      </div>
                      <div class="chat-bubble">{{ msg.message }}</div>
                    </div>

                    <!-- Current user -->
                    <div v-else class="chat chat-end">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            :src="avatar(current_user.picture)"
                            :alt="current_user.name"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        You
                        <time class="text-xs opacity-50 ms-1">{{
                          msg.created_at
                        }}</time>
                      </div>
                      <div class="chat-bubble">
                        {{ msg.message }}
                      </div>
                    </div>
                  </template>
                </div>
              </div>
              <div class="mt-3">
                <div
                  class="relative flex items-end gap-2 bg-base-100 rounded-2xl border border-base-300 p-2 focus-within:ring-2 focus-within:ring-primary/20 transition-all"
                >
                  <textarea
                    ref="textareaRef"
                    v-model="message"
                    @input="adjustHeight"
                    @keydown="handleKeydown"
                    placeholder="Type a message..."
                    class="textarea textarea-ghost focus:bg-transparent focus:outline-none w-full py-2 px-0 resize-none leading-relaxed overflow-hidden list-scroll"
                    rows="1"
                    style="min-height: 40px; box-sizing: border-box"
                  ></textarea>

                  <button
                    @click="sendMessage"
                    :disabled="!message.trim()"
                    class="btn btn-primary btn-circle btn-sm my-auto"
                  >
                    <i class="pi pi-send text-lg"></i>
                  </button>
                </div>
              </div>
            </div>
          </template>
        </div>

        <div class="w-xl">
          <ul
            class="list bg-base-100 rounded-box shadow-md border-2 border-green-primary-1"
          >
            <li class="p-4 pb-2 text-sm font-semibold opacity-60 tracking-wide">
              MEMBERS ({{ currentMembers.length }})
            </li>
            <div class="h-72 overflow-y-auto list-scroll me-1.5">
              <li
                v-for="member in currentMembers"
                :key="member.id"
                class="list-row"
              >
                <div>
                  <img
                    class="size-10 rounded-box"
                    :src="member.picture || '/profile-images/default.png'"
                  />
                </div>
                <div>
                  <div class="flex items-center gap-1">
                    {{ member.name }}
                    <span
                      v-if="member.id === current_user.id"
                      class="badge badge-primary badge-xs"
                      >You</span
                    >
                  </div>
                  <div class="text-xs uppercase font-semibold opacity-60">
                    {{ member.position || "N/A" }}
                  </div>
                </div>
              </li>
            </div>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tabs-lift {
  > .tab {
    --tab-border-colors: #0000 #0000 #4f8f75 #0000;

    &:is(.tab-active, [aria-selected="true"]):not(.tab-disabled, [disabled]),
    &:is(input:checked, label:has(:checked)) {
      --tab-border: var(--border) var(--border) 0 var(--border);
      --tab-border-colors: #4f8f75 #4f8f75 #0000 #4f8f75;
      --tab-paddings: 0 calc(var(--tab-p) - var(--border)) var(--border)
        calc(var(--tab-p) - var(--border));
      --tab-inset: auto auto 0 auto;
      --tab-grad: calc(69% - var(--border));
      --radius-start: radial-gradient(
        circle at top left,
        #0000 var(--tab-grad),
        #4f8f75 calc(var(--tab-grad) + 0.25px),
        #4f8f75 calc(var(--tab-grad) + var(--border)),
        var(--tab-bg) calc(var(--tab-grad) + var(--border) + 0.25px)
      );
      --radius-end: radial-gradient(
        circle at top right,
        #0000 var(--tab-grad),
        #4f8f75 calc(var(--tab-grad) + 0.25px),
        #4f8f75 calc(var(--tab-grad) + var(--border)),
        var(--tab-bg) calc(var(--tab-grad) + var(--border) + 0.25px)
      );
    }
  }
  &:has(.tab-content) {
    > .tab:first-child {
      &:not(.tab-active, [aria-selected="true"]) {
        --tab-border-colors: #4f8f75 #4f8f75 #0000 #4f8f75;
      }
    }
  }
}

.list-scroll::-webkit-scrollbar {
  width: 10px;
}
.list-scroll::-webkit-scrollbar-thumb {
  border-radius: 5px;
  background-color: var(--color-green-primary-3);
}
.list-scroll::-webkit-scrollbar-thumb:hover {
  background-color: var(--color-green-primary-1);
}
.list-scroll::-webkit-scrollbar-track {
  margin: 6px;
  border-radius: 5px;
  background-color: var(--color-base-300);
}
</style>
