Build CSV Ask Desk in THIS folder: C:\xampp\htdocs\csv-ask-desk

A recruiter-ready demo that answers English questions about a seeded CSV of Harbor Desk orders.

Laravel 13 + Vue 3 starter kit is already here if present. Reuse it. Do not create a second app inside NewPortfolio.

## Must satisfy

- Models: `CsvAsk` (question, generated_filter json, answer, row_count).
- Seed `database/seeders/data/orders.csv` (~20 fictional orders: id, customer, city, status, total). Copy into sqlite `orders` table.
- `App\Services\Csv\CsvAskService`: LLM proposes a filter (status/city/min_total), a PHP guard allows only those keys, then query sqlite. Reject raw SQL from the model. Timeout 12s. Never 500.
- `App\Services\Llm\LlmClient` with `FakeLlmClient` in tests and when no `GEMINI_API_KEY`. Optional Gemini when key is set.
- UI: question box, sample chips ("shipped to Austin", "orders over 100"), answer + matching rows table.
- Pest: "shipped orders in Austin" returns only shipped Austin rows; a DROP-like model output is rejected; FakeLlm bound; guest POST 200.
- README mermaid: Question → LLM filter JSON → guard → sqlite rows. How to run without API key.

## GitHub

- git config user.name "Oscar Munoz" / user.email "oscarxxi@gmail.com"
- Commit: feat: add csv ask desk
- Public https://github.com/oscarmmi/csv-ask-desk
- gh repo edit --description "Ask English questions about a CSV of orders with a guarded filter (Laravel + Vue)." --add-topic laravel --add-topic vue --add-topic ai --add-topic php --add-topic csv
- Stop. Print the URL.

No Impeccable. No Redis. No real customer data.
