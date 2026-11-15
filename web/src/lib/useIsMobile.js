import { onBeforeUnmount, onMounted, ref } from 'vue';

/** Chhoti screen (phone) hai ya nahi. Phone pe table ki jagah cards dikhate hain. */
export function useIsMobile(query = '(max-width: 767px)') {
    const isMobile = ref(false);
    let media = null;
    const update = () => {
        isMobile.value = media.matches;
    };

    onMounted(() => {
        media = window.matchMedia(query);
        update();
        media.addEventListener('change', update);
    });

    onBeforeUnmount(() => media?.removeEventListener('change', update));

    return isMobile;
}
