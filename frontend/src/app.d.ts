// Kiểu toàn cục của SvelteKit.
declare global {
  namespace App {
    interface Error {
      message: string;
      code?: string;
    }
  }
}

export {};
