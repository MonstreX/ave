import { showToast } from '../ui/toast.js'
import { trans } from '../../utils/translations.js'

/**
 * Handle cache clear menu item clicks
 */
export default function initCacheClear() {
    const routePrefix = document.querySelector('meta[name="ave-route-prefix"]')?.getAttribute('content') || 'admin'

    document.querySelectorAll('a[href^="#cache-clear-"]').forEach((link) => {
        link.addEventListener('click', async (event) => {
            event.preventDefault()
            event.stopPropagation()

            const type = link.getAttribute('href').replace('#cache-clear-', '')

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

                const response = await fetch(`/${routePrefix}/cache/clear/${type}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                })

                const data = await response.json()

                if (data.success) {
                    showToast('success', data.message)
                } else {
                    showToast('danger', data.message || trans('cache.error'))
                }
            } catch (error) {
                console.error('Cache clear error:', error)
                showToast('danger', trans('cache.error'))
            }
        })
    })
}
