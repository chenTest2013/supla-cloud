<template>
    <carousel-page header="My OAuth apps"
        tile="my-oauth-app-tile"
        endpoint="oauth-clients"
        create-new-label="Register a new OAuth application"
        details-route="myOauthApp"
        :limit="$user.userData.limits.oauthClient"
        class="my-oauth-apps-page"></carousel-page>
</template>

<script>
    import CarouselPage from "src/common/pages/carousel-page";
    import MyOauthAppTile from "./my-oauth-app-tile";
    import Vue from "vue";

    Vue.component('MyOauthAppTile', MyOauthAppTile);

    export default {
        components: {CarouselPage}
    };
</script>

<style>
    .my-oauth-apps-page .carousel-title {
        display: none;
    }
</style>
