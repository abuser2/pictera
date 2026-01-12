import { defineStore } from 'pinia'
import api from '../utils/api'

export const useProfileAlbumsStore = defineStore('profileAlbums', {
  state: () => ({
    albums: []
  }),

  actions: {
    // Fetch albums for a specific user
    async fetchAll(userId) {
      try {
        // Fetch public albums for other users
        const res = await api.get(`/albums?user_id=${userId}`)
        // normalize response: support both res.data.data and res.data
        const albums = res?.data?.data ?? res?.data ?? []
        this.albums = (Array.isArray(albums) ? albums : []).map(a => ({
          ...a,
          photos: Array.isArray(a?.photos) ? a.photos : [] // ensure photos is an array
        }))
        return this.albums
      } catch (err) {
        console.error('Failed to fetch albums:', err)
        this.albums = []
        return []
      }
    },

    async fetchPhotos(albumId) {
      const album = this.albums.find(a => a.id === albumId)
      if (!album) return []
      try {
        const res = await api.get(`/albums/${albumId}`)
        const photos = res?.data?.data?.photos ?? res?.data?.photos ?? []
        album.photos = Array.isArray(photos) ? photos : []
        return album.photos
      } catch (err) {
        console.error(`Failed to fetch photos for album ${albumId}:`, err)
        album.photos = album.photos || []
        return album.photos
      }
    },

    async create(name) {
      try {
        const res = await api.post('/albums', { name })
        const album = res?.data?.data ?? res?.data
        if (!album) return null
        album.photos = Array.isArray(album.photos) ? album.photos : []
        this.albums.unshift(album)
        return album
      } catch (err) {
        console.error('Failed to create album:', err)
        throw err
      }
    },

    async remove(id) {
      try {
        await api.delete(`/albums/${id}`)
        this.albums = this.albums.filter(a => a.id !== id)
      } catch (err) {
        console.error(`Failed to remove album ${id}:`, err)
        throw err
      }
    }
  }
})
