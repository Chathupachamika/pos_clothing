import { vi } from 'vitest'

// Mock window methods
window.scrollTo = vi.fn()

// Mock validators
window.isNaN = (value) => Number.isNaN(Number(value))

// Add any other global test setup here
