import { createRouter, createWebHistory } from 'vue-router'
import { authService } from '../services/api'

// Views (NOT pages)
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import TodosHome from '../views/Todoshome.vue'

// New feature views (still inside /views)
import TodosCreateView from '../views/TodosCreate.vue'
import TodosListView from '../views/TodosList.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    component: LoginView,
    meta: { guestOnly: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterView,
    meta: { guestOnly: true }
  },

  {
    path: '/todos',
    name: 'TodosHome',
    component: TodosHome,
    meta: { requiresAuth: true }
  },

  {
    path: '/todos/create',
    name: 'TodosCreate',
    component: TodosCreateView,
    meta: { requiresAuth: true }
  },

  {
    path: '/todos/list',
    name: 'TodosList',
    component: TodosListView,
    meta: { requiresAuth: true }
  },

  // fallback
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Auth guard
router.beforeEach((to, from, next) => {
  const isAuthenticated = authService.isAuthenticated()

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/')
  }
  else if (to.meta.guestOnly && isAuthenticated) {
    next('/todos')
  }
  else {
    next()
  }
})

export default router
