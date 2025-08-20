<script setup>
import { ref, watch } from "vue";
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker, LPopup } from "@vue-leaflet/vue-leaflet";
import L from "leaflet";

const props = defineProps({
  locations: {
    type: Array,
    required: true,
  },
  center: {
    type: Array,
    default: () => [15.1465, 120.5794],
  },
  zoom: {
    type: Number,
    default: 13,
  },
  fitBounds: {
    type: Boolean,
    default: false,
  },
});

const map = ref(null); // To hold the map instance
const mapReady = ref(false);

function onMapReady() {
  mapReady.value = true;
}

// This watcher now only reacts to changes in locations and the fitBounds prop.
watch(
  [() => props.locations, () => props.fitBounds, mapReady],
  ([newLocations, shouldFit, ready]) => {
    if (!ready || !map.value?.leafletObject) return;

    if (shouldFit) {
      const bounds = L.latLngBounds(
        newLocations.map((loc) => [loc.latitude, loc.longitude])
      );
      map.value.leafletObject.fitBounds(bounds);
    } else {
      map.value.leafletObject.setView(props.center, props.zoom);
    }
  },
  { immediate: true }
);
</script>

<template>
  <div class="h-[400px] w-full rounded-lg overflow-hidden">
    <l-map
      ref="map"
      :center="props.center"
      :zoom="props.zoom"
      @ready="onMapReady"
    >
      <l-tile-layer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
      />

      <l-marker
        v-for="(location, index) in props.locations"
        :key="`marker-${index}`"
        :lat-lng="[location.latitude, location.longitude]"
      >
        <l-popup>
          <slot name="popup" :location="location">
            <p>Lat: {{ location.latitude }}</p>
            <p>Lng: {{ location.longitude }}</p>
          </slot>
        </l-popup>
      </l-marker>
    </l-map>
  </div>
</template>
