# 🎮 GUIDE : Comment uploader une image de couverture

## Avec Postman (Recommandé pour tester)

### Étape 1 : S'authentifier
1. **POST** `/auth`
2. Body (JSON) :
```json
{
  "email": "admin@example.com",
  "password": "milanscroll"
}
```
3. Copier le `token` reçu

### Étape 2 : Créer un jeu avec image

**URL** : `POST https://127.0.0.1:8000/api/videogames`

**Headers** :
```
Authorization: Bearer TON_TOKEN_ICI
```

**Body** : Sélectionner **form-data** (pas JSON !)

| KEY | TYPE | VALUE |
|-----|------|-------|
| title | Text | "Elden Ring" |
| description | Text | "Un RPG épique" |
| releaseDate | Text | "2022-02-25" |
| editorId | Text | "1" |
| categoryIds | Text | `[1,2,3]` |
| coverImage | **File** | [Sélectionner une image] |

⚠️ **Important** : 
- `categoryIds` doit être du texte JSON : `[1,2,3]`
- `coverImage` doit être de type **File**, pas Text

### Étape 3 : Mettre à jour l'image d'un jeu

**URL** : `POST https://127.0.0.1:8000/api/videogames/1`
(⚠️ Utilise POST, pas PUT pour FormData !)

**Body** : form-data

| KEY | TYPE | VALUE |
|-----|------|-------|
| coverImage | File | [Nouvelle image] |
| title | Text | "Nouveau titre" (optionnel) |

---


## 🔍 Voir l'image uploadée

Une fois le jeu créé, l'API retourne :

```json
{
  "id": 1,
  "title": "The Witcher 4",
  "coverImage": "/uploads/covers/67339a1234567.jpg",
  ...
}
```

L'image est accessible à :
```
https://127.0.0.1:8000/uploads/covers/67339a1234567.jpg
```

Dans ton HTML :
```html
<img src="https://127.0.0.1:8000/uploads/covers/67339a1234567.jpg" alt="Cover">
```

---
<br><br><br><br><br><br>


## 🎯 Exemple complet avec Swagger UI (complicado avocado)

1. Va sur `https://127.0.0.1:8000/api/doc`
2. Clique sur **POST /api/videogames**
3. Clique sur **Try it out**
4. Change **Media type** de `application/json` à **`multipart/form-data`**
5. Remplis les champs
6. Pour `coverImage`, clique **Choose File**
7. Execute !

---

## 🐛 Problèmes courants

### "Content-Type boundary error"
❌ **Cause** : Tu as défini manuellement Content-Type
✅ **Solution** : Retire le header Content-Type, laisse le navigateur le gérer

### "categoryIds must be an array"
❌ **Cause** : Tu as envoyé `"1,2,3"` au lieu de `"[1,2,3]"`
✅ **Solution** : `formData.append('categoryIds', JSON.stringify([1,2,3]))`

### "Method not allowed" sur update
❌ **Cause** : Tu utilises PUT avec FormData
✅ **Solution** : Utilise POST pour les updates avec fichiers

### "File not uploaded"
❌ **Cause** : Le champ file n'est pas de type "File" dans Postman
✅ **Solution** : Sélectionne "File" dans le dropdown, pas "Text"
