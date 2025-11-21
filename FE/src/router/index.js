import { createRouter, createWebHistory } from 'vue-router'
import AlbumsView from '../views/AlbumsView.vue'
import AlbumDetail from '../views/AlbumDetail.vue'
import ProfileView from '../views/ProfileView.vue'
import HomeView from '../views/HomeView.vue'

const routes = [
  { path: '/', redirect: '/home' },
  { path: '/home', name: 'home', component: HomeView },
  { path: '/profile', name: 'profile', component: ProfileView },
  { path: '/albums', name: 'albums', component: AlbumsView },
  { path: '/albums/:id', name: 'album', component: AlbumDetail, props: true },
]

export default createRouter({ history: createWebHistory(), routes })
