import { createRouter, createWebHistory } from 'vue-router'
import MediaPlayer from '../components/MediaPlayer.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import AdminLayout from '../views/AdminLayout.vue'
import AssetManager from '../views/AssetManager.vue'
import PlaylistManager from '../views/PlaylistManager.vue'
import PlaylistEditor from '../views/PlaylistEditor.vue'
import UserManager from '../views/UserManager.vue'
import DashboardView from '../views/DashboardView.vue'
import LandingPage from '../views/LandingPage.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: LandingPage
    },
    {
      path: '/play/:slug?',
      name: 'player',
      component: MediaPlayer,
      props: true
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView
    },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/admin/dashboard'
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: DashboardView
        },
        {
          path: 'assets',
          name: 'assets',
          component: AssetManager
        },
        {
          path: 'playlists',
          name: 'playlists',
          component: PlaylistManager
        },
        {
          path: 'playlists/:id',
          name: 'playlist-editor',
          component: PlaylistEditor
        },
        {
          path: 'users',
          name: 'users',
          component: UserManager
        }
      ]
    }
  ]
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router
