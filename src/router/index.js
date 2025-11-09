import { createRouter, createWebHistory } from 'vue-router'
import AlbumsView from '../views/AlbumsView.vue'
import AlbumDetail from '../views/AlbumDetail.vue'
import ProfileView from '../views/ProfileView.vue'

const routes = [
  { path: '/', redirect: '/albums' },
  { path: '/home', name: 'home', redirect: '/albums' },
  { path: '/profile', name: 'profile', component: ProfileView },
  { path: '/albums', name: 'albums', component: AlbumsView },
  { path: '/albums/:id', name: 'album', component: AlbumDetail, props: true },
]

export default createRouter({ history: createWebHistory(), routes })
