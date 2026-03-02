<script setup></script>

<template>
  <Head title="Chat" />
  <div class="p-2 @lg:p-4 @3xl:p-8 @5xl:p-10 @7xl:p-12">
    <div class="h-[80vh] flex flex-col">
      <div class="flex flex-1 flex-col sm:flex-row gap-5 mx-4 mb-5">
        <!-- chat section -->
        <div class="tabs tabs-lift w-full h-full [--border:2px]">
          <input
            type="radio"
            name="my_tabs_3"
            class="tab"
            aria-label="Core Group"
            checked="checked"
          />
          <div class="tab-content bg-base-100 border-green-primary-1 p-6 pe-3">
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

          <input
            type="radio"
            name="my_tabs_3"
            class="tab"
            aria-label="Employees"
          />
          <div class="tab-content bg-base-100 border-green-primary-1 p-6">
            Employees Chat Group Here
          </div>

          <input
            type="radio"
            name="my_tabs_3"
            class="tab"
            aria-label="Interns"
          />
          <div class="tab-content bg-base-100 border-green-primary-1 p-6">
            Interns Chat Group Heres
          </div>
        </div>

        <!-- member section -->
        <div class="w-xl">
          <ul
            class="list bg-base-100 rounded-box shadow-md border-2 border-green-primary-1"
          >
            <li class="p-4 pb-2 text-sm opacity-60 tracking-wide">Members</li>

            <li class="list-row">
              <div>
                <img
                  class="size-10 rounded-box"
                  src="https://img.daisyui.com/images/profile/demo/1@94.webp"
                />
              </div>
              <div>
                <div>Dio Lupa</div>
                <div class="text-xs uppercase font-semibold opacity-60">
                  Remaining Reason
                </div>
              </div>
            </li>

            <li class="list-row">
              <div>
                <img
                  class="size-10 rounded-box"
                  src="https://img.daisyui.com/images/profile/demo/4@94.webp"
                />
              </div>
              <div>
                <div>Ellie Beilish</div>
                <div class="text-xs uppercase font-semibold opacity-60">
                  Bears of a fever
                </div>
              </div>
            </li>

            <li class="list-row">
              <div>
                <img
                  class="size-10 rounded-box"
                  src="https://img.daisyui.com/images/profile/demo/3@94.webp"
                />
              </div>
              <div>
                <div>Sabrino Gardener</div>
                <div class="text-xs uppercase font-semibold opacity-60">
                  Cappuccino
                </div>
              </div>
            </li>
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
