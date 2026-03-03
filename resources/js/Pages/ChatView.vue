<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
  auth_permissions: Object,
  group_members: Object,
});

// Track which group is currently selected to show the right members
const activeTab = ref(
  props.auth_permissions.can_core
    ? "core"
    : props.auth_permissions.can_employees
      ? "employees"
      : "interns",
);

const currentMembers = computed(() => {
  return props.group_members[activeTab.value] || [];
});
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
              :checked="activeTab === 'core'"
              @change="activeTab = 'core'"
            />
            <div
              class="tab-content bg-base-100 border-green-primary-1 p-6 pe-3"
            >
              <div class="h-[60vh] overflow-hidden">
                <div
                  class="h-full overflow-y-auto pe-5 pt-3 flex flex-col list-scroll"
                >
                  <div class="flex-grow" />

                  <div v-for="n in 10" :key="n">
                    <div class="chat chat-start">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            alt="Tailwind CSS chat bubble component"
                            src="https://img.daisyui.com/images/profile/demo/kenobee@192.webp"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        Obi-Wan Kenobi
                        <time class="text-xs opacity-50">12:45</time>
                      </div>
                      <div class="chat-bubble">You were the Chosen One!</div>
                    </div>

                    <div class="chat chat-end">
                      <div class="chat-image avatar">
                        <div class="w-10 rounded-full">
                          <img
                            alt="Tailwind CSS chat bubble component"
                            src="https://img.daisyui.com/images/profile/demo/anakeen@192.webp"
                          />
                        </div>
                      </div>
                      <div class="chat-header">
                        Anakin <time class="text-xs opacity-50">12:46</time>
                      </div>
                      <div class="chat-bubble">I hate you!</div>
                    </div>
                  </div>
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
              :checked="activeTab === 'employees'"
              @change="activeTab = 'employees'"
            />
            <div class="tab-content bg-base-100 border-green-primary-1 p-6">
              <div class="h-[60vh] overflow-y-auto">
                Employees Chat Content...
              </div>
            </div>
          </template>

          <template v-if="auth_permissions.can_interns">
            <input
              type="radio"
              name="chat_tabs"
              class="tab"
              aria-label="Interns"
              :checked="activeTab === 'interns'"
              @change="activeTab = 'interns'"
            />
            <div class="tab-content bg-base-100 border-green-primary-1 p-6">
              <div class="h-[60vh] overflow-y-auto">
                Interns Chat Content...
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
                    :src="
                      member.picture ||
                      'https://img.daisyui.com/images/profile/demo/1@94.webp'
                    "
                  />
                </div>
                <div>
                  <div>{{ member.name }}</div>
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
