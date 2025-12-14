import { defineStore } from 'pinia'
import api from '../utils/api'

export const useProfileAlbumsStore = defineStore('profileAlbums', {
  state: () => ({
    albums: []
  }),

  actions: {
    async fetchAll() {
      const res = await api.get('/albums?type=owned') // or adjust endpoint
      this.albums = (res.data?.data || res.data).map(a => ({
        ...a,
        photos: [] // placeholder for photos
      }))
    },

    async fetchPhotos(albumId) {
      const album = this.albums.find(a => a.id === albumId)
      if (!album) return
      const { data } = await api.get(`/albums/${albumId}`)
      album.photos = data?.data?.photos || data.photos || []
    },

    async create(name) {
      const res = await api.post('/albums', { name })
      const album = res.data?.data ?? res.data
      album.photos = []
      this.albums.unshift(album)
    },

    async remove(id) {
      await api.delete(`/albums/${id}`)
      this.albums = this.albums.filter(a => a.id !== id)
    },

    visibleAlbums(userId, visibility = null) {
        return this.albums.filter(a => {
            // only show albums belonging to this user
            if (a.user_id !== userId && a.user?.id !== userId) return false
            if (!visibility) return true
            return a.visibility === visibility
        })
    }
  }
})